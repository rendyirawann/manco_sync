<?php

namespace App\Http\Controllers\Frontend\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\AnimeService;
use Illuminate\Http\Request;

class AnimeController extends Controller
{
    public function __construct(protected AnimeService $anime) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q !== '') {
            return view('frontend.portal.anime.index', [
                'ongoing'   => $this->anime->search($q),
                'completed' => [],
                'query'     => $q,
            ]);
        }

        return view('frontend.portal.anime.index', [
            'ongoing'   => $this->anime->ongoing(),
            'completed' => $this->anime->completed(),
            'query'     => '',
        ]);
    }

    public function detail(string $id)
    {
        $data = $this->anime->detail($id);
        abort_if(!$data, 404, 'Anime tidak ditemukan.');

        return view('frontend.portal.anime.detail', ['anime' => $data, 'id' => $id]);
    }

    public function watch(string $episodeId)
    {
        $ep = $this->anime->episode($episodeId);
        abort_if(!$ep, 404, 'Episode tidak ditemukan.');

        return view('frontend.portal.anime.watch', ['ep' => $ep, 'episodeId' => $episodeId]);
    }

    /** AJAX: resolve a streaming-server id to its embed URL (for quality/server switching). */
    public function server(string $serverId)
    {
        $data = $this->anime->server($serverId);
        return response()->json([
            'url' => $data['url'] ?? $data['streamingUrl'] ?? null,
        ]);
    }
}
