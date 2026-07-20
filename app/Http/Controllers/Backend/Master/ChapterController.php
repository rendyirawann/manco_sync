<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\Chapter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ChapterController extends Controller
{
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'manga_id' => 'required|uuid|exists:mangas,id',
            'chapter_number' => 'required|numeric|min:0',
            'title' => 'required|string|max:255',
            'pages' => 'nullable|array',
            'pages.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120', // Max 5MB per page
            'zip_file' => 'nullable|file|mimes:zip|max:51200', // Max 50MB ZIP
        ]);

        $validator->after(function ($validator) use ($request) {
            if (!$request->hasFile('pages') && !$request->hasFile('zip_file')) {
                $validator->errors()->add('pages', 'Anda harus mengunggah file ZIP atau minimal 1 halaman gambar.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            $manga = Manga::findOrFail($request->manga_id);

            // Check if chapter number already exists for this manga
            $exists = Chapter::where('manga_id', $manga->id)
                ->where('chapter_number', $request->chapter_number)
                ->exists();

            if ($exists) {
                return response()->json([
                    'errors' => ['chapter_number' => ['Nomor chapter ini sudah terdaftar di manga ini.']]
                ]);
            }

            // Create Chapter in Postgres
            $chapter = new Chapter();
            $chapter->manga_id = $manga->id;
            $chapter->chapter_number = $request->chapter_number;
            $chapter->title = $request->title;
            // Generate clean slug: manga-slug-chapter-10
            $chapter->slug = Str::slug($manga->title . '-chapter-' . $request->chapter_number);
            $chapter->save();

            $pageUrls = [];
            $mangaSlug = $manga->slug;
            $chapterNum = $chapter->chapter_number;

            // Handle ZIP file upload if present
            if ($request->hasFile('zip_file')) {
                $zipFile = $request->file('zip_file');
                $zip = new \ZipArchive();
                $tempPath = storage_path('app/temp_zip_' . Str::uuid());

                if ($zip->open($zipFile->getRealPath()) === true) {
                    $zip->extractTo($tempPath);
                    $zip->close();

                    // Read and collect all image files recursively from temp directory
                    $files = [];
                    $dirIterator = new \RecursiveDirectoryIterator($tempPath, \RecursiveDirectoryIterator::SKIP_DOTS);
                    $iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);

                    foreach ($iterator as $fileInfo) {
                        if ($fileInfo->isFile()) {
                            $ext = strtolower($fileInfo->getExtension());
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                                $files[] = $fileInfo->getRealPath();
                            }
                        }
                    }

                    if (empty($files)) {
                        \File::deleteDirectory($tempPath);
                        return response()->json([
                            'errors' => ['zip_file' => ['File ZIP tidak berisi gambar berformat JPG, JPEG, PNG, atau WEBP.']]
                        ]);
                    }

                    // Sort files naturally by filename (e.g., page-1.jpg, page-2.jpg, page-10.jpg)
                    usort($files, function ($a, $b) {
                        return strnatcmp(basename($a), basename($b));
                    });

                    // Store images to physical public disk storage
                    foreach ($files as $idx => $filePath) {
                        $pageNumber = sprintf('%03d', $idx + 1); // 001, 002, 003...
                        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                        $filename = 'page-' . $pageNumber . '-' . Str::uuid() . '.' . $ext;

                        $path = "manga/{$mangaSlug}/chapters/{$chapterNum}";
                        Storage::disk('public')->put("{$path}/{$filename}", file_get_contents($filePath));

                        $pageUrls[] = "/storage/{$path}/{$filename}";
                    }

                    // Clean up temp directory
                    \File::deleteDirectory($tempPath);
                } else {
                    return response()->json([
                        'errors' => ['zip_file' => ['Gagal mengekstrak file ZIP.']]
                    ]);
                }
            } else {
                // Process standard multi-file uploads
                $files = $request->file('pages');
                
                // Sort files naturally by original original filename
                usort($files, function ($a, $b) {
                    return strnatcmp($a->getClientOriginalName(), $b->getClientOriginalName());
                });

                foreach ($files as $idx => $file) {
                    $pageNumber = sprintf('%03d', $idx + 1); // 001, 002, etc.
                    $filename = 'page-' . $pageNumber . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                    
                    $path = "manga/{$mangaSlug}/chapters/{$chapterNum}";
                    Storage::disk('public')->putFileAs($path, $file, $filename);

                    $pageUrls[] = "/storage/{$path}/{$filename}";
                }
            }

            // Sync with NoSQL via Rust API Wrapper!
            $syncSuccess = $chapter->syncPagesToNoSql($pageUrls);

            if (!$syncSuccess) {
                logger()->error("NoSQL sync failed for chapter {$chapter->id} during creation.");
            }

            // Activity Log
            activity()
                ->useLog('chapter_management')
                ->causedBy(auth()->user())
                ->performedOn($chapter)
                ->log("Menambahkan chapter {$chapter->chapter_number} ({$chapter->title}) ke manga {$manga->title}");

            DB::commit();

            return response()->json([
                'success' => 'Chapter dan Halaman berhasil ditambahkan!',
                'judul' => 'Berhasil',
                'nosql_synced' => $syncSuccess
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Gagal menambahkan chapter: ' . $e->getMessage(),
                'judul' => 'Error'
            ], 500);
        }
    }

    public function show($id)
    {
        $chapter = Chapter::with('manga')->findOrFail($id);
        
        // Fetch pages dynamically from NoSQL (bridged through Rust!)
        $pages = $chapter->getPagesFromNoSql();

        return view('backend.master.chapter.show', compact('chapter', 'pages'));
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $chapter = Chapter::with('manga')->findOrFail($id);
            $manga = $chapter->manga;

            // Delete folder on physical storage containing the chapter pages
            $mangaSlug = $manga->slug;
            $chapterNum = $chapter->chapter_number;
            $path = "manga/{$mangaSlug}/chapters/{$chapterNum}";

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->deleteDirectory($path);
            }

            // Delete Postgres record (will cascade if configured, but we call delete explicitly)
            $chapter->delete();

            // Activity Log
            activity()
                ->useLog('chapter_management')
                ->causedBy(auth()->user())
                ->log("Menghapus chapter {$chapter->chapter_number} dari manga {$manga->title}");

            DB::commit();

            return response()->json([
                'success' => 'Chapter berhasil dihapus!',
                'judul' => 'Berhasil'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Gagal menghapus chapter: ' . $e->getMessage(),
                'judul' => 'Error'
            ], 500);
        }
    }
}
