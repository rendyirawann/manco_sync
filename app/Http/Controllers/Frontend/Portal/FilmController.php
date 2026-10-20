<?php

namespace App\Http\Controllers\Frontend\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\TmdbClient;
use App\Services\Portal\SubtitleService;
use Illuminate\Http\Request;

/**
 * Film & TV portal. Metadata via TMDB; streams via the self-hosted
 * TMDB-Embed-API. Distinct from the Sanka StreamController pipeline.
 */
class FilmController extends Controller
{
    public function __construct(protected TmdbClient $tmdb, protected SubtitleService $subs) {}

    public function index(Request $r)
    {
        $type = $r->query('type', 'movie');
        if (!in_array($type, ['movie', 'tv'], true)) {
            $type = 'movie';
        }
        $tab  = (string) $r->query('tab', 'Populer');
        $page = max(1, (int) $r->query('page', 1));
        $q    = trim((string) $r->query('q', ''));

        if (!$this->tmdb->hasKey()) {
            $items = [];
        } elseif ($q !== '') {
            $items = $this->tmdb->search($type, $q, $page);
        } else {
            $items = match ($tab) {
                'Trending'  => $this->tmdb->trending($type, $page),
                'Top Rated' => $this->tmdb->topRated($type, $page),
                'Indonesia' => $this->tmdb->indonesian($type, $page),
                default     => $this->tmdb->popular($type, $page),
            };
        }

        return view('frontend.portal.film.index', [
            'type' => $type, 'tab' => $tab, 'page' => $page, 'q' => $q,
            'items' => $items, 'hasKey' => $this->tmdb->hasKey(),
        ]);
    }

    public function detail(string $type, string $id, Request $r)
    {
        abort_unless(in_array($type, ['movie', 'tv'], true), 404);
        $data = $this->tmdb->detail($type, $id);
        abort_if(!$data, 404, 'Judul tidak ditemukan.');

        // For TV, load episodes of the chosen (default 1st) season.
        $season = max(1, (int) $r->query('season', 1));
        $episodes = $type === 'tv' ? $this->tmdb->tvEpisodes($id, $season) : [];

        $providers = $this->tmdb->watchProviders($type, $id);

        return view('frontend.portal.film.detail', compact('type', 'id', 'data', 'season', 'episodes', 'providers'));
    }

    public function watch(string $type, string $id, Request $r)
    {
        abort_unless(in_array($type, ['movie', 'tv'], true), 404);
        $season  = $r->query('season') !== null ? (int) $r->query('season') : null;
        $episode = $r->query('episode') !== null ? (int) $r->query('episode') : null;

        $data = $this->tmdb->detail($type, $id);

        // Split: browser-playable (HLS .m3u8 / .mp4) vs download-only (.mkv/.zip/direct files).
        $playable = [];
        $downloads = [];
        foreach ($this->tmdb->streams($type, $id, $season, $episode) as $s) {
            // Penyedia yang tak bisa diputar langsung di browser (butuh header Referer/Origin
            // yang tak bisa dikirim browser, atau sering 429) tidak ditampilkan.
            if (in_array(strtolower((string) ($s['provider'] ?? '')), ['netmirror', 'onetouchtv'], true)) {
                continue;
            }
            $u = strtolower((string) ($s['url'] ?? ''));
            if ($u === '') {
                continue;
            }
            // HLS = .m3u8, the embed's own m3u8 proxy, or a playlist endpoint (vixsrc etc. serve m3u8 without the extension).
            $isHls = str_contains($u, '.m3u8')
                || str_contains($u, 'm3u8-proxy')
                || str_contains($u, 'vixsrc.to/playlist')
                || str_contains($u, '/playlist/');
            $isMp4 = (bool) preg_match('/\.mp4(\?|$)/', $u);
            if ($isHls || $isMp4) {
                $s['hls'] = $isHls;
                $playable[] = $s;
            } else {
                $downloads[] = $s; // .mkv / .zip / worker-download links — browsers can't stream these
            }
        }
        // HLS first (works cross-browser via hls.js), then MP4.
        // Urutan: audio English dulu (paling cocok dengan subtitle Indonesia), lalu HLS
        // (kualitas adaptif) sebelum mp4, lalu kualitas tertinggi.
        $rank = function ($x) {
            $n = strtolower((string) ($x['name'] ?? ''));
            $lang = str_contains($n, '[english]') || !str_contains($n, '[') ? 0 : 1;
            $q = (int) preg_replace('/\D/', '', (string) ($x['quality'] ?? '0'));
            return [$lang, ($x['hls'] ?? false) ? 0 : 1, -$q];
        };
        usort($playable, fn ($a, $b) => $rank($a) <=> $rank($b));
        // Subtitles (OpenSubtitles) → WebVTT track URLs. tracks() already orders
        // them Indonesia/English first, so the first <track> is the default and
        // matches the player's captions.language = 'id'.
        $subtitles = array_map(fn ($t) => [
            'label' => $t['label'], 'lang' => $t['lang'], 'src' => route('portal.sub', ['f' => $t['file_id']]),
        ], $this->subs->tracks($type, $id, $season, $episode, (int) ($data['runtime'] ?? 0)));

        // Server iframe (TMDB ID) selalu tersedia di belakang stream langsung.
        // Sub Indonesia kita (CORS *) diteruskan ke penyedia yang menerima file eksternal.
        // Untuk pemutar embed: alamat berakhiran .vtt (sebagian pemutar mengecek ekstensi).
        $idTrack = collect($this->subs->tracks($type, $id, $season, $episode, (int) ($data['runtime'] ?? 0)))->firstWhere('lang', 'id');
        $idSub = $idTrack ? route('portal.sub.vtt', ['f' => $idTrack['file_id']]) : null;
        $playable = array_merge($playable, $this->tmdb->embeds($type, $id, $season, $episode, $idSub));

        return view('frontend.portal.film.watch', [
            'type' => $type, 'id' => $id, 'data' => $data,
            'season' => $season, 'episode' => $episode,
            'playable' => $playable, 'downloads' => $downloads, 'subtitles' => $subtitles,
        ]);
    }
}
