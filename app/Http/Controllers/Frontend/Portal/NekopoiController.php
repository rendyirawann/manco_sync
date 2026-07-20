<?php

namespace App\Http\Controllers\Frontend\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\NekopoiClient;
use Illuminate\Http\Request;

/**
 * 18+ (Nekopoi). Gated behind an age-confirmation stored in session.
 * Source is flaky (often empty) — degrades gracefully.
 */
class NekopoiController extends Controller
{
    public function __construct(protected NekopoiClient $neko) {}

    protected function gated(Request $r): bool
    {
        return $r->session()->get('adult_ok') === true;
    }

    public function index(Request $r)
    {
        if (!$this->gated($r)) {
            return view('frontend.portal.dewasa.gate');
        }
        $q = trim((string) $r->query('q', ''));
        $items = $q !== '' ? $this->neko->search($q) : $this->neko->latest();
        return view('frontend.portal.dewasa.index', compact('items', 'q'));
    }

    public function enter(Request $r)
    {
        $r->session()->put('adult_ok', true);
        return redirect()->route('portal.dewasa.index');
    }

    public function detail(Request $r)
    {
        abort_unless($this->gated($r), 403);
        $data = $this->neko->detail((string) $r->query('url', ''));
        abort_if(!$data, 404, 'Tidak ditemukan.');
        return view('frontend.portal.dewasa.detail', compact('data'));
    }

    public function random(Request $r)
    {
        abort_unless($this->gated($r), 403);
        $data = $this->neko->random();
        abort_if(!$data, 404, 'Sumber sedang kosong.');
        return view('frontend.portal.dewasa.detail', compact('data'));
    }
}
