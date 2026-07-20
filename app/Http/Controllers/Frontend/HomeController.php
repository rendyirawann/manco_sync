<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\Genre;
use App\Models\Chapter;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $splashImage = asset('images/frontend/splash-default.jpg');

        $featured = Manga::with(['genres', 'chapters' => fn($q) => $q->latest()->take(2)])
            ->withCount('chapters')
            ->withMax('chapters', 'chapter_number')
            ->orderByDesc('rating')->take(6)->get();

        $popular = Manga::withCount('chapters')
            ->withMax('chapters', 'chapter_number')
            ->orderByDesc('rating')->take(12)->get();

        $latest = Manga::with(['genres', 'chapters' => fn($q) => $q->latest()->take(2)])
            ->withMax('chapters', 'chapter_number')
            ->latest()->take(15)->get();

        $genres = Genre::withCount('mangas')->orderByDesc('mangas_count')->take(8)->get();

        $firstGenre = $genres->first();
        $genreManga = $firstGenre
            ? $firstGenre->mangas()->withCount('chapters')->withMax('chapters', 'chapter_number')->take(12)->get()
            : collect();

        $completed = Manga::where('status', 'completed')
            ->withCount('chapters')
            ->withMax('chapters', 'chapter_number')
            ->orderByDesc('rating')->take(6)->get();

        return view('frontend.home', compact(
            'splashImage', 'featured', 'popular',
            'latest', 'genres', 'genreManga', 'completed'
        ));
    }

    public function latest()
    {
        $mangas = Manga::with(['genres', 'chapters' => fn($q) => $q->latest()->take(2)])
                       ->latest()->paginate(24);
        return view('frontend.latest', compact('mangas'));
    }

    public function popular()
    {
        $mangas = Manga::withCount('chapters')
                       ->orderByDesc('rating')->paginate(24);
        return view('frontend.popular', compact('mangas'));
    }

    public function daftar(Request $request)
    {
        $query = Manga::withCount('chapters');
        if ($request->type)   $query->where('type', $request->type);
        if ($request->status) $query->where('status', $request->status);
        if ($request->genre)  $query->whereHas('genres', fn($q) => $q->where('slug', $request->genre));
        if ($request->q)      $query->where('title', 'ilike', '%'.$request->q.'%');
        $mangas = $query->orderBy('title')->paginate(30);
        $genres = Genre::orderBy('name')->get();
        return view('frontend.daftar', compact('mangas', 'genres'));
    }

    public function byType($type)
    {
        $mangas = Manga::where('type', $type)->withCount('chapters')
                       ->orderByDesc('rating')->paginate(24);
        return view('frontend.by-type', compact('mangas', 'type'));
    }

    public function show($slug)
    {
        $manga = Manga::with(['genres', 'chapters' => fn($q) => $q->orderBy('chapter_number')])
                      ->where('slug', $slug)->firstOrFail();
        $related = Manga::whereHas('genres', fn($q) => $q->whereIn('genres.id', $manga->genres->pluck('id')))
                        ->where('id', '!=', $manga->id)->take(8)->get();
        return view('frontend.manga-detail', compact('manga', 'related'));
    }

    public function read($slug)
    {
        $chapter = Chapter::with('manga')->where('slug', $slug)->firstOrFail();
        
        // Fetch pages dynamically from NoSQL (bridged through Rust!)
        $pages = $chapter->getPagesFromNoSql();
        
        // Fetch adjacent chapters for next/prev navigation
        $prevChapter = Chapter::where('manga_id', $chapter->manga_id)
            ->where('chapter_number', '<', $chapter->chapter_number)
            ->orderByDesc('chapter_number')
            ->first();
            
        $nextChapter = Chapter::where('manga_id', $chapter->manga_id)
            ->where('chapter_number', '>', $chapter->chapter_number)
            ->orderBy('chapter_number')
            ->first();
            
        // Get all chapters of this manga for a selector dropdown
        $chaptersList = Chapter::where('manga_id', $chapter->manga_id)
            ->orderBy('chapter_number')
            ->get();
            
        return view('frontend.chapter-read', compact('chapter', 'pages', 'prevChapter', 'nextChapter', 'chaptersList'));
    }

    public function genreAjax($slug)
    {
        $genre  = Genre::where('slug', $slug)->firstOrFail();
        $mangas = $genre->mangas()->withCount('chapters')->take(12)->get();
        $html   = view('frontend.partials.manga-grid-items', compact('mangas'))->render();
        return response()->json(['html' => $html]);
    }

    public function search(Request $request)
    {
        $q      = $request->get('q', '');
        $mangas = Manga::where('title', 'ilike', "%$q%")->take(8)->get(['id','title','slug','cover_image','type']);
        return response()->json($mangas->map(fn($m) => [
            'title'     => $m->title,
            'slug'      => $m->slug,
            'type'      => $m->type,
            'cover_url' => $m->cover_url,
            'url'       => route('frontend.manga.show', $m->slug),
        ]));
    }

    /**
     * Public Image Proxy to bypass CORS, referer blocks, and ISP blocks for manga chapter pages.
     */
    public function imageProxy(Request $request)
    {
        $encodedUrl = $request->query('url');
        if (empty($encodedUrl)) {
            abort(400, 'Missing url parameter.');
        }

        // Decode URL (supports base64 URL-safe encoding)
        $url = base64_decode($encodedUrl, true);
        if ($url === false || !filter_var($url, FILTER_VALIDATE_URL)) {
            // Fallback try raw urldecode
            $url = urldecode($encodedUrl);
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                abort(400, 'Invalid URL.');
            }
        }

        try {
            // Securely download the image using server-side cURL with custom User-Agent
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://mangadex.org/',
                'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8'
            ])->timeout(12)->get($url);

            if (!$response->successful()) {
                abort(404, 'Failed to fetch image from target server.');
            }

            $contentType = $response->header('Content-Type') ?? 'image/jpeg';
            $imageContent = $response->body();

            // Return response with premium browser caching headers to minimize host network usage
            return response($imageContent)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'public, max-age=2592000, immutable'); // Cache for 30 days
        } catch (\Exception $e) {
            abort(500, 'Image Proxy Error: ' . $e->getMessage());
        }
    }
}
