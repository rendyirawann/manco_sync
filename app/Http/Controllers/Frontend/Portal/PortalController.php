<?php

namespace App\Http\Controllers\Frontend\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\SourceClient;
use App\Services\Portal\TmdbClient;
use App\Services\Portal\SubtitleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PortalController extends Controller
{
    public function __construct(protected SourceClient $client, protected TmdbClient $tmdb) {}

    /**
     * Cyberpunk multi-content hub — the main "beranda": a live mix of anime,
     * donghua, komik (Sanka) + film (TMDB). URLs are fully resolved here so the
     * view stays dumb. Empty/down rows are skipped to keep the beranda clean.
     */
    public function hub()
    {
        $feed = [];

        // Sanka rows: [category, source, list-path, label, icon]
        foreach ([
            ['anime',   'otakudesu', '/anime/ongoing-anime',     'Anime Ongoing',   'fa-tv'],
            ['donghua', 'anichin',   '/anime/donghua/ongoing/1', 'Donghua Terbaru', 'fa-dragon'],
            ['comic',   'komikindo', '/comic/komikindo/latest/1', 'Komik Terbaru',   'fa-book-open'],
            ['novel',   'sakuranovel', '/novel/sakuranovel/home?page=1', 'Novel Terbaru', 'fa-feather-pointed'],
        ] as [$cat, $src, $path, $label, $icon]) {
            $items = $this->client->list($cat, $src, $path);
            if (!$items) {
                continue;
            }
            $feed[] = [
                'label' => $label, 'icon' => $icon,
                'seeAllUrl' => route('portal.stream.index', $cat) . '?source=' . $src,
                'items' => array_map(fn ($it) => [
                    'title' => $it['title'], 'poster' => $it['poster'], 'meta' => $it['meta'],
                    'url' => route('portal.stream.detail', ['category' => $cat, 'id' => $it['id']]) . '?source=' . $src,
                ], array_slice($items, 0, 12)),
            ];
        }

        // Film row (TMDB)
        $films = $this->tmdb->popular('movie', 1);
        if ($films) {
            $feed[] = [
                'label' => 'Film Populer', 'icon' => 'fa-film',
                'seeAllUrl' => route('portal.film.index'),
                'items' => array_map(fn ($it) => [
                    'title' => $it['title'], 'poster' => $it['poster'], 'meta' => $it['meta'],
                    'url' => route('portal.film.detail', ['type' => $it['type'], 'id' => $it['id']]),
                ], array_slice($films, 0, 12)),
            ];
        }

        $nav = [
            ['label' => 'Anime',           'icon' => 'fa-tv',          'url' => route('portal.stream.index', 'anime')],
            ['label' => 'Donghua',         'icon' => 'fa-dragon',      'url' => route('portal.stream.index', 'donghua')],
            ['label' => 'Manga & Manhwa',  'icon' => 'fa-book-open',   'url' => route('portal.stream.index', 'comic')],
            ['label' => 'Novel',           'icon' => 'fa-feather-pointed', 'url' => route('portal.stream.index', 'novel')],
            ['label' => 'Drama',           'icon' => 'fa-clapperboard','url' => route('portal.stream.index', 'drama')],
            ['label' => 'Film & Movie',    'icon' => 'fa-film',        'url' => route('portal.film.index')],
            ['label' => 'Live TV',         'icon' => 'fa-tower-broadcast', 'url' => route('portal.tv.index')],
        ];

        return view('frontend.portal.hub', ['feed' => $feed, 'nav' => $nav]);
    }

    /**
     * Safe image proxy for hotlink-protected sources (komiku reader pages, etc).
     * Sends the target's own origin as Referer and blocks private/loopback hosts.
     */
    public function image(Request $request)
    {
        $url = base64_decode((string) $request->query('u', ''), true);
        if ($url !== false) {
            $url = str_replace(' ', '%20', $url);
        }
        if ($url === false || !filter_var($url, FILTER_VALIDATE_URL)) {
            abort(400);
        }
        $parts  = parse_url($url);
        $scheme = $parts['scheme'] ?? '';
        $host   = $parts['host'] ?? '';
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            abort(400);
        }
        $ip = gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            abort(403);
        }
        try {
            $headers = [
                'User-Agent' => (string) config('services.manco.scraper_ua'),
                'Accept'     => 'image/avif,image/webp,image/*,*/*;q=0.8',
            ];

            $referer = $request->query('ref');
            if ($referer) {
                $headers['Referer'] = $referer;
            } else {
                if (preg_match('/imageainewgeneration\.lol|himmga\.lat|gaimgame\.pics/i', $host)) {
                    $headers['Referer'] = 'https://bacakomik.my/';
                } else {
                    $headers['Referer'] = $scheme . '://' . $host . '/';
                }
            }

            $res = Http::withHeaders($headers)->timeout(12)->get($url);

            // Self-healing fallback: if blocked by hotlink-protection, retry with bacakomik referer
            if (($res->status() === 403 || $res->status() === 401) && (!isset($headers['Referer']) || $headers['Referer'] !== 'https://bacakomik.my/')) {
                $headers['Referer'] = 'https://bacakomik.my/';
                $res = Http::withHeaders($headers)->timeout(12)->get($url);
            }

            if (!$res->successful()) {
                abort(404);
            }
            return response($res->body(), 200)
                ->header('Content-Type', $res->header('Content-Type') ?: 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=604800');
        } catch (\Throwable $e) {
            abort(404);
        }
    }

    /** SSRF-safe URL check → decoded url or abort. */
    protected function safeUrl(string $b64): string
    {
        $url = base64_decode($b64, true);
        $parts = $url ? parse_url($url) : false;
        if (!$url || !$parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            abort(400);
        }
        $ip = gethostbyname($parts['host']);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            abort(403);
        }
        return $url;
    }

    protected function resolveUrl(string $base, string $rel): string
    {
        if (preg_match('#^https?://#i', $rel)) return $rel;
        $p = parse_url($base);
        $origin = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
        if (str_starts_with($rel, '//')) return ($p['scheme'] ?? 'https') . ':' . $rel;
        if (str_starts_with($rel, '/'))  return $origin . $rel;
        $dir = preg_replace('#/[^/]*$#', '/', $p['path'] ?? '/');
        return $origin . $dir . $rel;
    }

    /**
     * HLS proxy: fetch an .m3u8 server-side and rewrite its segment/sub-playlist/key
     * URLs back through this proxy — defeats CORS + hotlink for streams that block
     * cross-origin browser playback. Sub-playlists recurse; segments go via seg().
     */
    public function hls(Request $request)
    {
        $url = $this->safeUrl((string) $request->query('u', ''));
        try {
            $res = Http::withHeaders(['User-Agent' => (string) config('services.manco.scraper_ua'), 'Accept' => '*/*'])
                ->timeout(15)->get($url);
            if (!$res->successful()) abort(502);
        } catch (\Throwable $e) {
            abort(502);
        }
        $hls = route('portal.hls');
        $seg = route('portal.seg');
        $out = [];
        foreach (preg_split('/\r?\n/', $res->body()) as $line) {
            $t = trim($line);
            if ($t === '') { $out[] = $line; continue; }
            if ($t[0] === '#') {
                // rewrite URI="..." on #EXT-X-KEY / #EXT-X-MEDIA
                $out[] = preg_replace_callback('/URI="([^"]+)"/', function ($m) use ($url, $hls) {
                    return 'URI="' . $hls . '?u=' . urlencode(base64_encode($this->resolveUrl($url, $m[1]))) . '"';
                }, $line);
                continue;
            }
            $abs = $this->resolveUrl($url, $t);
            $base = strtok($abs, '?');
            $out[] = (stripos($base, '.m3u8') !== false)
                ? $hls . '?u=' . urlencode(base64_encode($abs))
                : $seg . '?u=' . urlencode(base64_encode($abs));
        }
        return response(implode("\n", $out), 200)
            ->header('Content-Type', 'application/vnd.apple.mpegurl')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'no-cache');
    }

    /** Stream an HLS segment/key through the proxy with CORS. */
    public function seg(Request $request)
    {
        $url = $this->safeUrl((string) $request->query('u', ''));
        try {
            $res = Http::withHeaders(['User-Agent' => (string) config('services.manco.scraper_ua')])
                ->timeout(25)->get($url);
        } catch (\Throwable $e) {
            abort(502);
        }
        return response($res->body(), $res->successful() ? 200 : $res->status())
            ->header('Content-Type', $res->header('Content-Type') ?: 'video/mp2t')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'public, max-age=30');
    }

    /** Serve an OpenSubtitles file as WebVTT for <track>. */
    public function sub(Request $request, SubtitleService $subs)
    {
        $vtt = $subs->vtt((string) $request->query('f', ''));
        abort_if(!$vtt, 404);
        return response($vtt, 200)
            ->header('Content-Type', 'text/vtt; charset=UTF-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
