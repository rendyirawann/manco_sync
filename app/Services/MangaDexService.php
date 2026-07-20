<?php

namespace App\Services;

use App\Models\Manga;
use App\Models\Chapter;
use App\Models\Genre;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MangaDexService
{
    protected $baseUrl = 'https://api.mangadex.org';

    public function searchManga(string $title, int $limit = 10, ?string $language = null)
    {
        $queryParams = [
            'title' => $title,
            'limit' => $limit,
            'includes' => ['cover_art'],
            'contentRating' => ['safe', 'suggestive']
        ];

        $response = Http::withHeaders([
            'User-Agent' => 'MancoSync/1.0 (mancosync-development@gmail.com)'
        ])->get("{$this->baseUrl}/manga", $queryParams);

        if (!$response->successful()) {
            return [];
        }

        $results = [];
        $data = $response->json()['data'] ?? [];

        foreach ($data as $item) {
            $id = $item['id'];
            $attributes = $item['attributes'];
            $titleEn = $attributes['title']['en'] ?? $attributes['title']['ja-ro'] ?? array_values($attributes['title'])[0] ?? 'Untitled';
            
            // Find Cover file
            $coverFileName = null;
            if (isset($item['relationships'])) {
                foreach ($item['relationships'] as $rel) {
                    if ($rel['type'] === 'cover_art' && isset($rel['attributes']['fileName'])) {
                        $coverFileName = $rel['attributes']['fileName'];
                        break;
                    }
                }
            }

            // Fallback cover if cover_art relationship was not fully loaded
            $coverUrl = 'https://placehold.co/200x300?text=' . urlencode($titleEn);
            if ($coverFileName) {
                $coverUrl = "https://uploads.mangadex.org/covers/{$id}/{$coverFileName}.256.jpg";
            }

            $results[] = [
                'id' => $id,
                'title' => $titleEn,
                'type' => $attributes['publicationDemographic'] ?? 'manga',
                'status' => $attributes['status'] ?? 'ongoing',
                'year' => $attributes['year'] ?? null,
                'cover_url' => $coverUrl,
                'description' => $attributes['description']['en'] ?? $attributes['description']['id'] ?? 'No description available.',
                'available_languages' => $attributes['availableTranslatedLanguages'] ?? []
            ];
        }

        return $results;
    }

    /**
     * Import a full manga and its chapters from MangaDex.
     */
    public function importManga(string $mangaDexId, string $language = 'id')
    {
        // Prevent PHP execution time limit timeout during long imports
        set_time_limit(300);

        // 1. Fetch Manga Details
        $response = Http::withHeaders([
            'User-Agent' => 'MancoSync/1.0 (mancosync-development@gmail.com)'
        ])->get("{$this->baseUrl}/manga/{$mangaDexId}", [
            'includes' => ['cover_art', 'author', 'artist']
        ]);

        if (!$response->successful()) {
            throw new \Exception("Gagal mengambil data manga dari MangaDex: " . $response->body());
        }

        $mangaData = $response->json()['data'];
        $attributes = $mangaData['attributes'];
        
        $title = $attributes['title']['en'] ?? $attributes['title']['ja-ro'] ?? array_values($attributes['title'])[0] ?? 'Untitled';
        $slug = Str::slug($title);

        // Check if already exists in Postgres
        $manga = Manga::where('slug', $slug)->first();
        if (!$manga) {
            $manga = new Manga();
            $manga->title = $title;
            $manga->slug = $slug;
        }

        // Map type
        $rawType = $attributes['publicationDemographic'] ?? 'manga';
        $manga->type = in_array($rawType, ['manga', 'manhwa', 'manhua']) ? $rawType : 'manga';

        // Map status
        $rawStatus = $attributes['status'] ?? 'ongoing';
        $manga->status = $rawStatus === 'completed' ? 'completed' : ($rawStatus === 'hiatus' ? 'hiatus' : 'ongoing');

        $manga->description = $attributes['description']['id'] ?? $attributes['description']['en'] ?? 'No description available.';
        $manga->release_year = $attributes['year'] ?? null;
        $manga->rating = 8.5; // Default rating fallback
        $manga->source = 'mangadex';
        $manga->language = $language;

        // Find Author & Artist
        $authorName = null;
        $artistName = null;
        $coverFileName = null;

        foreach ($mangaData['relationships'] as $rel) {
            if ($rel['type'] === 'author' && isset($rel['attributes']['name'])) {
                $authorName = $rel['attributes']['name'];
            }
            if ($rel['type'] === 'artist' && isset($rel['attributes']['name'])) {
                $artistName = $rel['attributes']['name'];
            }
            if ($rel['type'] === 'cover_art' && isset($rel['attributes']['fileName'])) {
                $coverFileName = $rel['attributes']['fileName'];
            }
        }

        $manga->author = $authorName ?? 'Unknown';
        $manga->artist = $artistName ?? 'Unknown';

        // Download cover image
        if ($coverFileName) {
            $coverSourceUrl = "https://uploads.mangadex.org/covers/{$mangaDexId}/{$coverFileName}";
            try {
                $imageContents = Http::withHeaders([
                    'User-Agent' => 'MancoSync/1.0 (mancosync-development@gmail.com)'
                ])->get($coverSourceUrl)->body();
                $extension = pathinfo($coverFileName, PATHINFO_EXTENSION);
                $filename = 'cover-' . Str::uuid() . '-' . time() . '.' . $extension;
                Storage::disk('public')->put("manga/covers/{$filename}", $imageContents);
                $manga->cover_image = $filename;
            } catch (\Exception $e) {
                logger()->error("Failed to download cover from MangaDex: " . $e->getMessage());
            }
        }

        $manga->save();

        // 2. Map and Sync Genres
        $genreIds = [];
        if (isset($attributes['tags'])) {
            foreach ($attributes['tags'] as $tag) {
                $tagName = $tag['attributes']['name']['en'] ?? null;
                if ($tagName) {
                    $genre = Genre::firstOrCreate(
                        ['slug' => Str::slug($tagName)],
                        ['name' => $tagName]
                    );
                    $genreIds[] = $genre->id;
                }
            }
        }
        $manga->genres()->sync($genreIds);

        // 3. Fetch Chapters (Feed)
        // Order by chapter ascending
        $chaptersResponse = Http::withHeaders([
            'User-Agent' => 'MancoSync/1.0 (mancosync-development@gmail.com)'
        ])->get("{$this->baseUrl}/manga/{$mangaDexId}/feed", [
            'translatedLanguage' => [$language], // Only fetch the selected language (no fallback)
            'limit' => 100,
            'order' => ['chapter' => 'asc'],
            'contentRating' => ['safe', 'suggestive']
        ]);

        if ($chaptersResponse->successful()) {
            $chaptersList = $chaptersResponse->json()['data'] ?? [];
            
            foreach ($chaptersList as $chapItem) {
                $chapAttr = $chapItem['attributes'];
                $chapterNum = $chapAttr['chapter'] ?? null;
                $chapId = $chapItem['id'];

                if ($chapterNum === null) {
                    continue; // Skip specials without chapter numbers
                }

                // Skip official redirects / external chapters (like MangaPlus) that do not host images on MangaDex
                $externalUrl = $chapAttr['externalUrl'] ?? null;
                if ($externalUrl !== null) {
                    continue;
                }

                // Check if chapter already exists in Postgres
                $exists = Chapter::where('manga_id', $manga->id)
                    ->where('chapter_number', $chapterNum)
                    ->exists();

                if ($exists) {
                    continue; // Skip duplicates
                }

                $chapter = new Chapter();
                $chapter->manga_id = $manga->id;
                $chapter->chapter_number = $chapterNum;
                $chapter->title = $chapAttr['title'] ?? "Chapter {$chapterNum}";
                $chapter->slug = Str::slug($manga->title . '-chapter-' . $chapterNum);
                $chapter->save();

                // 4. Fetch Page Images URLs from MangaDex CDN
                $pagesResponse = Http::withHeaders([
                    'User-Agent' => 'MancoSync/1.0 (mancosync-development@gmail.com)'
                ])->get("{$this->baseUrl}/at-home/server/{$chapId}");
                if ($pagesResponse->successful()) {
                    $pagesData = $pagesResponse->json();
                    $host = $pagesData['baseUrl'];
                    $hash = $pagesData['chapter']['hash'];
                    $fileNames = $pagesData['chapter']['data'] ?? [];

                    $pageUrls = [];
                    foreach ($fileNames as $fileName) {
                        // Use persistent uploads.mangadex.org CDN domain that never expires
                        $pageUrls[] = "https://uploads.mangadex.org/data/{$hash}/{$fileName}";
                    }

                    // Save directly to MongoDB using our Rust API wrapper!
                    $chapter->syncPagesToNoSql($pageUrls);
                }

                // Add a small 2-second delay to bypass MangaDex rate limits (429 Too Many Requests)
                sleep(2);
            }
        }

        return $manga;
    }
}
