<?php

namespace App\Http\Controllers\Frontend\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\NekopoiClient;
use App\Services\Portal\SourceClient;
use Illuminate\Http\Request;

/**
 * 18+ hub (Nekopoi anime + Mangasusuku manga). Access is enforced by the
 * `superadmin` middleware on the routes (must be logged in as Superadmin), so no
 * per-action auth check is needed here. Nekopoi is flaky → degrades gracefully.
 */
class NekopoiController extends Controller
{
    public function __construct(protected NekopoiClient $neko, protected SourceClient $client) {}

    public function index(Request $r)
    {
        $q = trim((string) $r->query('q', ''));
        // Two clearly separated 18+ sub-sources: Mangasusuku (manga, reliable) +
        // Nekopoi (anime, flaky). Fetch both so the page is never blank.
        $manga = $q !== ''
            ? $this->client->search('comic18', 'mangasusuku', $q)
            : $this->client->list('comic18', 'mangasusuku', '/comic/mangasusuku/list/1');
        $neko = $q !== '' ? $this->neko->search($q) : $this->neko->latest();
        return view('frontend.portal.dewasa.index', compact('manga', 'neko', 'q'));
    }

    public function detail(Request $r)
    {
        $data = $this->neko->detail((string) $r->query('url', ''));
        if (!$data) {
            return redirect()->route('portal.dewasa.index')
                ->with('portal_msg', 'Konten tidak ditemukan / sumber Nekopoi sedang down. Coba Komik 18+ (Mangasusuku) yang lebih stabil.');
        }
        return view('frontend.portal.dewasa.detail', compact('data'));
    }

    public function random(Request $r)
    {
        $data = $this->neko->random();
        // Nekopoi domains rotate/get blocked → often empty. Don't 404; guide the user.
        if (!$data) {
            return redirect()->route('portal.dewasa.index')
                ->with('portal_msg', 'Sumber Nekopoi sedang kosong/down (domain sering rotasi). Coba lagi nanti, atau buka Komik 18+ (Mangasusuku).');
        }
        return view('frontend.portal.dewasa.detail', compact('data'));
    }
}
