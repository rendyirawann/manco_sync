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

        return view('frontend.portal.film.detail', compact('type', 'id', 'data', 'season', 'episodes'));
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
        usort($playable, fn ($a, $b) => (int) ($b['hls'] ?? false) <=> (int) ($a['hls'] ?? false));

        // Subtitles (OpenSubtitles) → WebVTT track URLs. tracks() already orders
        // them Indonesia/English first, so the first <track> is the default and
        // matches the player's captions.language = 'id'.
        $subtitles = array_map(fn ($t) => [
            'label' => $t['label'], 'lang' => $t['lang'], 'src' => route('portal.sub', ['f' => $t['file_id']]),
        ], $this->subs->tracks($type, $id, $season, $episode));

        return view('frontend.portal.film.watch', [
            'type' => $type, 'id' => $id, 'data' => $data,
            'season' => $season, 'episode' => $episode,
            'playable' => $playable, 'downloads' => $downloads, 'subtitles' => $subtitles,
        ]);
    }
}
