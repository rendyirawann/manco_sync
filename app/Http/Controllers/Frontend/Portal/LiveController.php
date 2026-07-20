<?php

namespace App\Http\Controllers\Frontend\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\LiveTvClient;
use Illuminate\Http\Request;

class LiveController extends Controller
{
    public function __construct(protected LiveTvClient $tv) {}

    public function index(Request $request)
    {
        $cats = $this->tv->categories();
        $cat  = (string) $request->query('cat', 'Sport');
        if (!isset($cats[$cat])) {
            $cat = 'Sport';
        }
        $q    = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $per  = 48;

        $all = $this->tv->channels($cat);
        if ($q !== '') {
            $all = array_values(array_filter($all, fn ($c) => stripos($c['name'], $q) !== false));
        }
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $per, $per);

        // Featured free-to-air (official TVRI HLS) — World Cup / bola.
        // TVRI Nasional carries World Cup on FTA and is confirmed live (200 + CORS ok).
        $tvriLogo = 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/eb/TVRILogo2019.svg/960px-TVRILogo2019.svg.png';
        $featured = [
            ['name' => 'TVRI Nasional — Bola / World Cup', 'url' => 'https://ott-balancer.tvri.go.id/live/eds/Nasional/hls/Nasional.m3u8', 'logo' => $tvriLogo],
            ['name' => 'TVRI World', 'url' => 'https://ott-balancer.tvri.go.id/live/eds/TVRIWorld/hls/TVRIWorld.m3u8', 'logo' => $tvriLogo],
        ];

        // Official free World Cup 2026 streams (TVRI holds Indonesian rights).
        // The dedicated match feed is on these authorized platforms (free), not on the
        // public redistribution HLS which gets slated/blacked-out during the match.
        // Only VERIFIED-reachable platforms (checked live). MAXStream is a named
        // TVRI WC OTT partner; Vidio is a legit ID streaming platform.
        $official = [
            ['name' => 'MAXStream', 'url' => 'https://maxstream.tv', 'desc' => 'OTT resmi (Telkomsel) — mitra WC TVRI'],
            ['name' => 'Vidio', 'url' => 'https://www.vidio.com', 'desc' => 'Platform streaming resmi Indonesia'],
        ];

        return view('frontend.portal.tv.index', compact('cats', 'cat', 'q', 'page', 'per', 'items', 'total', 'featured', 'official'));
    }

    public function watch(Request $request)
    {
        $url = base64_decode((string) $request->query('u', ''), true);
        abort_if($url === false || !filter_var($url, FILTER_VALIDATE_URL), 404);
        $name = (string) $request->query('n', 'Live TV');

        return view('frontend.portal.tv.watch', compact('url', 'name'));
    }
}
