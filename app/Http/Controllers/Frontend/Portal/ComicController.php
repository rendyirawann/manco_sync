<?php

namespace App\Http\Controllers\Frontend\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\ComicService;
use Illuminate\Http\Request;

class ComicController extends Controller
{
    public function __construct(protected ComicService $comic) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q !== '') {
            return view('frontend.portal.comic.index', [
                'latest'  => $this->comic->search($q),
                'popular' => [],
                'query'   => $q,
            ]);
        }

        return view('frontend.portal.comic.index', [
            'latest'  => $this->comic->latest(),
            'popular' => $this->comic->popular(),
            'query'   => '',
        ]);
    }

    public function detail(string $slug)
    {
        $data = $this->comic->detail($slug);
        abort_if(!$data || empty($data['title']), 404, 'Komik tidak ditemukan.');

        return view('frontend.portal.comic.detail', ['comic' => $data, 'slug' => $slug]);
    }

    public function read(string $chapter)
    {
        $data = $this->comic->chapter($chapter);
        abort_if(!$data, 404, 'Chapter tidak ditemukan.');

        return view('frontend.portal.comic.read', ['ch' => $data, 'chapter' => $chapter]);
    }
}
