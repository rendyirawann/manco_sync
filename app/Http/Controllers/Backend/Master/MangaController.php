<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\Genre;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Jenssegers\Agent\Agent;

class MangaController extends Controller
{
    public function index()
    {
        $genres = Genre::orderBy('name', 'asc')->get();
        return view('backend.master.manga.index', compact('genres'));
    }

    public function getDataMangas(Request $request)
    {
        if ($request->ajax()) {
            $query = Manga::with('genres')->orderBy('created_at', 'desc');

            if ($request->filled('filter_type')) {
                $query->where('type', $request->filter_type);
            }

            if ($request->filled('filter_status')) {
                $query->where('status', $request->filter_status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('cover', function ($row) {
                    return '<div class="symbol symbol-50px me-5">
                                <img src="' . $row->cover_url . '" alt="' . e($row->title) . '" class="w-40px h-60px rounded object-cover" style="object-fit: cover;" />
                            </div>';
                })
                ->addColumn('title_section', function ($row) {
                    $langBadge = '';
                    $lang = $row->language ?? 'id';
                    if ($lang === 'id') {
                        $langBadge = '<span class="badge badge-light-success fs-9 px-2 py-0 fw-bold text-uppercase ms-1 align-middle" style="font-size: 9px; line-height: 1.5;"><span class="fs-10 me-1">🇮🇩</span>INDO</span>';
                    } elseif ($lang === 'en') {
                        $langBadge = '<span class="badge badge-light-primary fs-9 px-2 py-0 fw-bold text-uppercase ms-1 align-middle" style="font-size: 9px; line-height: 1.5;"><span class="fs-10 me-1">🇬🇧</span>ENG</span>';
                    } else {
                        $langBadge = '<span class="badge badge-light-secondary fs-9 px-2 py-0 fw-bold text-uppercase ms-1 align-middle" style="font-size: 9px; line-height: 1.5;">' . e(strtoupper($lang)) . '</span>';
                    }

                    return '<div class="d-flex flex-column">
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <a href="' . route('mangas.show', $row->id) . '" class="text-gray-800 text-hover-primary mb-1 fw-bold">' . e($row->title) . '</a>
                                    ' . $langBadge . '
                                </div>
                                <span class="text-muted fs-7">Author: ' . e($row->author ?? 'Unknown') . '</span>
                            </div>';
                })
                ->addColumn('type_badge', function ($row) {
                    $badges = [
                        'manga' => 'badge-light-primary',
                        'manhwa' => 'badge-light-success',
                        'manhua' => 'badge-light-warning',
                    ];
                    $badge = $badges[strtolower($row->type)] ?? 'badge-light-info';
                    return '<span class="badge ' . $badge . ' fw-bold text-uppercase">' . e($row->type) . '</span>';
                })
                ->addColumn('source_badge', function ($row) {
                    $sourceStr = strtolower($row->source ?? 'manual');
                    if ($sourceStr === 'mangadex') {
                        return '<span class="badge badge-light-warning fw-bold text-uppercase"><i class="fas fa-robot text-warning me-1"></i> MangaDex</span>';
                    } elseif ($sourceStr === 'seeder') {
                        return '<span class="badge badge-light-danger fw-bold text-uppercase"><i class="fas fa-database text-danger me-1"></i> Seeder</span>';
                    } else {
                        return '<span class="badge badge-light-success fw-bold text-uppercase"><i class="fas fa-user text-success me-1"></i> Manual</span>';
                    }
                })
                ->addColumn('status_badge', function ($row) {
                    $badges = [
                        'ongoing' => 'bg-success',
                        'completed' => 'bg-info',
                        'hiatus' => 'bg-danger',
                    ];
                    $badge = $badges[strtolower($row->status)] ?? 'bg-secondary';
                    return '<span class="badge ' . $badge . ' text-white fw-bold text-uppercase">' . e($row->status) . '</span>';
                })
                ->addColumn('genres_list', function ($row) {
                    return $row->genres->map(function ($genre) {
                        return '<span class="badge badge-light-secondary fs-8 me-1 mb-1">' . e($genre->name) . '</span>';
                    })->implode('');
                })
                ->addColumn('action', function ($row) {
                    return '<div class="dropdown text-end">
                                <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions <i class="ki-outline ki-down fs-5 ms-1"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-dark fs-6">
                                    <li><a class="dropdown-item px-3" href="' . route('mangas.show', $row->id) . '">Detail & Chapters</a></li>
                                    <li><a class="dropdown-item px-3 btn-edit" href="javascript:void(0)" data-id="' . $row->id . '">Edit</a></li>
                                    <li><a class="dropdown-item px-3 text-danger" href="javascript:void(0)" data-id="' . $row->id . '" data-bs-toggle="modal" data-bs-target="#Modal_Hapus_Manga" id="getDeleteId">Hapus</a></li>
                                </ul>
                            </div>';
                })
                ->rawColumns(['cover', 'title_section', 'type_badge', 'source_badge', 'status_badge', 'genres_list', 'action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|in:manga,manhwa,manhua',
            'status' => 'required|in:ongoing,completed,hiatus',
            'author' => 'nullable|string|max:255',
            'artist' => 'nullable|string|max:255',
            'release_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'rating' => 'nullable|numeric|min:0|max:10',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpg,png,jpeg,webp|max:3072',
            'genres' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            $manga = new Manga();
            $manga->title = $request->title;
            $manga->slug = Str::slug($request->title);
            $manga->type = $request->type;
            $manga->status = $request->status;
            $manga->author = $request->author;
            $manga->artist = $request->artist;
            $manga->release_year = $request->release_year;
            $manga->rating = $request->rating;
            $manga->description = $request->description;

            // Handle cover image
            if ($request->hasFile('cover_image')) {
                $file = $request->file('cover_image');
                $filename = 'cover-' . Str::uuid() . '-' . time() . '.' . $file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('manga/covers', $file, $filename);
                $manga->cover_image = $filename;
            }

            $manga->save();

            // Sync Genres
            $manga->genres()->sync($request->genres);

            // Spatie Activity Log
            $agent = new Agent();
            activity()
                ->useLog('manga_management')
                ->causedBy(auth()->user())
                ->performedOn($manga)
                ->withProperties([
                    'ip' => $request->ip(),
                    'agent' => [
                        'browser' => $agent->browser(),
                        'os' => $agent->platform(),
                        'device' => $agent->device(),
                    ],
                    'new' => $manga->toArray(),
                ])
                ->log('Menambahkan manga baru: ' . $manga->title);

            DB::commit();

            return response()->json([
                'success' => 'Manga berhasil ditambahkan!',
                'judul' => 'Berhasil'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Gagal menambahkan manga: ' . $e->getMessage(),
                'judul' => 'Error'
            ], 500);
        }
    }

    public function show($id)
    {
        $manga = Manga::with('genres', 'chapters')->findOrFail($id);
        return view('backend.master.manga.show', compact('manga'));
    }

    public function edit($id)
    {
        $manga = Manga::with('genres')->findOrFail($id);
        
        $html = view('backend.master.manga.edit', [
            'manga' => $manga,
            'selectedGenres' => $manga->genres->pluck('id')->toArray(),
            'genres' => Genre::orderBy('name')->get(),
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function update(Request $request, $id)
    {
        $validator = \Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|in:manga,manhwa,manhua',
            'status' => 'required|in:ongoing,completed,hiatus',
            'author' => 'nullable|string|max:255',
            'artist' => 'nullable|string|max:255',
            'release_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'rating' => 'nullable|numeric|min:0|max:10',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpg,png,jpeg,webp|max:3072',
            'genres' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            $manga = Manga::findOrFail($id);
            $oldData = $manga->toArray();

            $manga->title = $request->title;
            $manga->slug = Str::slug($request->title);
            $manga->type = $request->type;
            $manga->status = $request->status;
            $manga->author = $request->author;
            $manga->artist = $request->artist;
            $manga->release_year = $request->release_year;
            $manga->rating = $request->rating;
            $manga->description = $request->description;

            // Handle cover image update
            if ($request->hasFile('cover_image')) {
                // Delete old cover
                if ($manga->cover_image && Storage::disk('public')->exists('manga/covers/' . $manga->cover_image)) {
                    Storage::disk('public')->delete('manga/covers/' . $manga->cover_image);
                }

                $file = $request->file('cover_image');
                $filename = 'cover-' . Str::uuid() . '-' . time() . '.' . $file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('manga/covers', $file, $filename);
                $manga->cover_image = $filename;
            }

            $manga->save();

            // Sync Genres
            $manga->genres()->sync($request->genres);

            // Spatie Activity Log
            $agent = new Agent();
            activity()
                ->useLog('manga_management')
                ->causedBy(auth()->user())
                ->performedOn($manga)
                ->withProperties([
                    'ip' => $request->ip(),
                    'agent' => [
                        'browser' => $agent->browser(),
                        'os' => $agent->platform(),
                        'device' => $agent->device(),
                    ],
                    'old' => $oldData,
                    'new' => $manga->toArray(),
                ])
                ->log('Mengubah manga: ' . $manga->title);

            DB::commit();

            return response()->json([
                'success' => 'Manga berhasil diperbarui!',
                'judul' => 'Berhasil'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Gagal memperbarui manga: ' . $e->getMessage(),
                'judul' => 'Error'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $manga = Manga::findOrFail($id);

            // Delete cover
            if ($manga->cover_image && Storage::disk('public')->exists('manga/covers/' . $manga->cover_image)) {
                Storage::disk('public')->delete('manga/covers/' . $manga->cover_image);
            }

            $manga->delete();

            // Spatie Activity Log
            activity()
                ->useLog('manga_management')
                ->causedBy(auth()->user())
                ->log('Menghapus manga: ' . $manga->title);

            DB::commit();

            return response()->json([
                'success' => 'Manga berhasil dihapus!',
                'judul' => 'Berhasil'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Gagal menghapus manga: ' . $e->getMessage(),
                'judul' => 'Error'
            ], 500);
        }
    }

    public function searchMangaDex(Request $request)
    {
        $query = $request->query('query');
        $language = $request->query('language'); // Receive the selected language filter!
        
        if (empty($query)) {
            return response()->json([]);
        }

        $service = app(\App\Services\MangaDexService::class);
        $results = $service->searchManga($query, 10, $language);

        return response()->json($results);
    }

    public function runImportMangaDex(Request $request)
    {
        $request->validate([
            'mangadex_id' => 'required|string|max:100',
            'language' => 'required|string|in:id,en',
        ]);

        try {
            $service = app(\App\Services\MangaDexService::class);
            $manga = $service->importManga($request->mangadex_id, $request->language);

            return response()->json([
                'success' => "Manga '{$manga->title}' dan seluruh chapternya berhasil diimpor otomatis dari MangaDex!",
                'judul' => 'Berhasil Impor'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal mengimpor manga: ' . $e->getMessage(),
                'judul' => 'Gagal Impor'
            ], 500);
        }
    }
}
