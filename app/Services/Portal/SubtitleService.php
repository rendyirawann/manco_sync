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
    public function tracks(string $type, string $id, ?int $season = null, ?int $episode = null): array
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
        $ck = 'ossearch:' . md5(json_encode($q));
        $cached = Cache::get($ck);
        if ($cached !== null) {
            return $cached;
        }
        try {
            $r = Http::withHeaders($this->headers())->timeout(15)->get($this->base . '/subtitles', $q);
            if (!$r->successful()) {
                return [];
            }
            $byLang = [];
            foreach (($r->json('data') ?? []) as $it) {
                $a    = $it['attributes'] ?? [];
                $lang = strtolower((string) ($a['language'] ?? '')); // API returns e.g. "zh-CN"
                $fid  = $a['files'][0]['file_id'] ?? null;
                if ($lang === '' || !$fid || isset($byLang[$lang])) {
                    continue; // first per language wins (already best by download_count)
                }
                $byLang[$lang] = [
                    'lang'    => $lang,
                    'label'   => self::LANGS[$lang] ?? strtoupper($lang),
                    'file_id' => (int) $fid,
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
            Cache::put($ck, $tracks, 3600);
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
            Cache::put($ck, $vtt, 86400); // quota is precious → cache a day
            return $vtt;
        } catch (\Throwable $e) {
            logger()->warning('OpenSubtitles download failed: ' . $e->getMessage());
            return null;
        }
    }

    protected function srtToVtt(string $srt): string
    {
        $srt = preg_replace('/^\xEF\xBB\xBF/', '', $srt);   // strip BOM
        $srt = str_replace("\r\n", "\n", $srt);
        // SRT timestamps use comma; WebVTT uses dot.
        $srt = preg_replace('/(\d{2}:\d{2}:\d{2}),(\d{3})/', '$1.$2', $srt);
        return "WEBVTT\n\n" . $srt;
    }
}
