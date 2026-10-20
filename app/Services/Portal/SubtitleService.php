<?php

namespace App\Services\Portal;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Film/TV subtitles via OpenSubtitles REST API (api.opensubtitles.com).
 * Needs a free API key (services.manco.opensubtitles_key). Search is by TMDB id;
 * download resolves an .srt which we convert to WebVTT for <track>. Download quota
 * is limited on the free tier, so converted VTT is cached a day.
 */
class SubtitleService
{
    protected string $key;
    protected string $ua = 'MancoSync v1.0';
    protected string $base = 'https://api.opensubtitles.com/api/v1';

    /**
     * Curated popular subtitle languages offered in the CC menu (valid
     * OpenSubtitles codes, lowercase). Only languages that actually have a
     * subtitle for the title show up as a <track>. Keys = query codes,
     * values = human labels shown in the player's captions menu.
     */
    protected const LANGS = [
        'id'    => 'Indonesia',
        'en'    => 'English',
        'ms'    => 'Melayu',
        'ar'    => 'العربية',
        'es'    => 'Español',
        'pt-br' => 'Português (BR)',
        'pt-pt' => 'Português (PT)',
        'fr'    => 'Français',
        'de'    => 'Deutsch',
        'it'    => 'Italiano',
        'nl'    => 'Nederlands',
        'ru'    => 'Русский',
        'ja'    => '日本語',
        'ko'    => '한국어',
        'zh-cn' => '中文 (简体)',
        'zh-tw' => '中文 (繁體)',
        'hi'    => 'हिन्दी',
        'pl'    => 'Polski',
    ];

    public function __construct()
    {
        $this->key = (string) config('services.manco.opensubtitles_key');
    }

    public function hasKey(): bool
    {
        return trim($this->key) !== '';
    }

    protected function headers(): array
    {
        return [
            'Api-Key'      => $this->key,
            'User-Agent'   => $this->ua,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Find the best subtitle per language for a title (many languages, so the
     * player's CC menu offers a real choice). One file per language — results
     * are download_count-sorted, so the first per language is the most popular.
     * @return array<int,array{lang:string,label:string,file_id:int}>
     */
    /** Bahasa yang subtitlenya diperiksa kelengkapannya (pemirsa utama). */
    protected const CHECKED_LANGS = ['id', 'en'];

    /** Subtitle dianggap lengkap bila baris terakhirnya >= 80% durasi film. */
    protected const MIN_COVERAGE = 0.8;

    /**
     * @param int $runtimeMin durasi film/episode (menit) dari TMDB; 0 = tidak diketahui,
     *                        maka pilihan tetap yang paling banyak diunduh.
     */
    public function tracks(string $type, string $id, ?int $season = null, ?int $episode = null, int $runtimeMin = 0): array
    {
        if (!$this->hasKey()) {
            return [];
        }
        $q = [
            'languages' => implode(',', array_keys(self::LANGS)),
            'order_by'  => 'download_count', // most-downloaded first => best pick per language
        ];
        if ($type === 'tv') {
            $q['parent_tmdb_id'] = $id;
            if ($season)  { $q['season_number'] = $season; }
            if ($episode) { $q['episode_number'] = $episode; }
        } else {
            $q['tmdb_id'] = $id;
        }
        $ck = 'ossearch2:' . md5(json_encode($q) . '|' . $runtimeMin);
        $cached = Cache::get($ck);
        if ($cached !== null) {
            return $cached;
        }
        try {
            $r = Http::withHeaders($this->headers())->timeout(15)->get($this->base . '/subtitles', $q);
            if (!$r->successful()) {
                return [];
            }
            // Kandidat per bahasa, urut download_count (terbanyak dulu). Subtitle
            // berkas-ganda (CD1/CD2) dilewati: hanya CD1 yang bisa kita putar,
            // sehingga sisanya hilang di pertengahan film.
            $cands = [];
            foreach (($r->json('data') ?? []) as $it) {
                $a    = $it['attributes'] ?? [];
                $lang = strtolower((string) ($a['language'] ?? '')); // API returns e.g. "zh-CN"
                $files = $a['files'] ?? [];
                $fid  = $files[0]['file_id'] ?? null;
                if ($lang === '' || !$fid || count($files) > 1) {
                    continue;
                }
                if (count($cands[$lang] ?? []) < 4) {
                    $cands[$lang][] = (int) $fid;
                }
            }

            $byLang = [];
            foreach ($cands as $lang => $fids) {
                $pick = $fids[0];
                if ($runtimeMin > 0 && in_array($lang, self::CHECKED_LANGS, true)) {
                    $pick = $this->mostComplete($fids, $runtimeMin * 60);
                }
                $byLang[$lang] = [
                    'lang'    => $lang,
                    'label'   => self::LANGS[$lang] ?? strtoupper($lang),
                    'file_id' => $pick,
                ];
            }
            // Indonesia & English first (this audience), then the rest A→Z by label.
            $prio = ['id' => 0, 'en' => 1];
            $tracks = array_values($byLang);
            usort($tracks, function ($a, $b) use ($prio) {
                $pa = $prio[$a['lang']] ?? 9;
                $pb = $prio[$b['lang']] ?? 9;
                return $pa === $pb ? strcmp($a['label'], $b['label']) : $pa <=> $pb;
            });
            Cache::put($ck, $tracks, 6 * 3600);
            return $tracks;
        } catch (\Throwable $e) {
            logger()->warning('OpenSubtitles search failed: ' . $e->getMessage());
            return [];
        }
    }

    /** Resolve a file_id → WebVTT string (downloads .srt, converts, caches). */
    public function vtt(string $fileId): ?string
    {
        if (!$this->hasKey() || $fileId === '') {
            return null;
        }
        $ck = 'osvtt:' . $fileId;
        $cached = Cache::get($ck);
        if ($cached !== null) {
            return $cached;
        }
        try {
            $d = Http::withHeaders($this->headers())->timeout(15)->post($this->base . '/download', ['file_id' => (int) $fileId]);
            $link = $d->json('link');
            if (!$link) {
                return null;
            }
            $srt = Http::timeout(15)->get($link)->body();
            if (!$srt) {
                return null;
            }
            $vtt = $this->srtToVtt($srt);
            Cache::put($ck, $vtt, 30 * 86400); // berkas subtitle tidak berubah; kuota unduh terbatas
            return $vtt;
        } catch (\Throwable $e) {
            logger()->warning('OpenSubtitles download failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Kandidat pertama yang mencakup >= 80% durasi; kalau tidak ada, yang
     * paling lengkap. Cakupan tiap berkas diingat 30 hari supaya kuota unduh
     * OpenSubtitles tidak habis untuk memeriksa ulang.
     */
    protected function mostComplete(array $fids, int $runtimeSec): int
    {
        $best = $fids[0];
        $bestCov = -1.0;
        foreach ($fids as $fid) {
            $cov = Cache::get('oscov:' . $fid);
            if ($cov === null) {
                $vtt = $this->vtt((string) $fid);
                if ($vtt === null) {
                    continue; // gagal unduh (mis. kuota): jangan dicatat, coba lagi lain waktu
                }
                $cov = $this->lastCueSeconds($vtt) / max(1, $runtimeSec);
                Cache::put('oscov:' . $fid, $cov, 30 * 86400);
            }
            if ($cov >= self::MIN_COVERAGE) {
                return (int) $fid;
            }
            if ($cov > $bestCov) {
                $best = $fid;
                $bestCov = $cov;
            }
        }
        return (int) $best;
    }

    /** Detik akhir baris subtitle terakhir. */
    protected function lastCueSeconds(string $vtt): int
    {
        if (!preg_match_all('/(\d{1,2}):(\d{2}):(\d{2})\.\d{3}\s*-->\s*(\d{1,2}):(\d{2}):(\d{2})/', $vtt, $m, PREG_SET_ORDER)) {
            return 0;
        }
        $last = 0;
        foreach ($m as $x) {
            $last = max($last, (int) $x[4] * 3600 + (int) $x[5] * 60 + (int) $x[6]);
        }
        return $last;
    }

    protected function srtToVtt(string $srt): string
    {
        // Banyak .srt Indonesia lama berenkode Windows-1252; pemutar membuang
        // baris yang bukan UTF-8 valid tanpa pesan apa pun.
        if (!mb_check_encoding($srt, 'UTF-8')) {
            $srt = mb_convert_encoding($srt, 'UTF-8', 'Windows-1252');
        }
        $srt = preg_replace('/^\xEF\xBB\xBF/', '', $srt);   // strip BOM
        $srt = str_replace(["\r\n", "\r"], "\n", $srt);
        // SRT: koma, jam boleh 1 digit ("0:12:34,567"). WebVTT: titik, jam 2 digit.
        $srt = preg_replace_callback('/(\d{1,2}):(\d{2}):(\d{2})[,.](\d{1,3})/', fn ($m) =>
            sprintf('%02d:%s:%s.%s', $m[1], $m[2], $m[3], str_pad($m[4], 3, '0')), $srt);
        return "WEBVTT\n\n" . $srt;
    }
}
