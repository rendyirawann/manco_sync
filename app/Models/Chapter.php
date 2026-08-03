<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;

class Chapter extends Model
{
    use HasUuids;

    protected $table = 'chapters';

    protected $fillable = [
        'manga_id',
        'chapter_number',
        'title',
        'slug',
        'views_count',
    ];

    protected $casts = [
        'chapter_number' => 'float',
        'views_count' => 'integer',
    ];

    /**
     * Get parent manga.
     */
    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class, 'manga_id', 'id');
    }

    /**
     * Helper to fetch this chapter's pages directly from the Rust / MongoDB API.
     * This bridges our Laravel and Rust backend synchronously on the backend!
     */
    public function getPagesFromNoSql(): array
    {
        try {
            // Rust container port is 8000. In local windows environment, we hit localhost:8000
            $response = Http::timeout(3)->get(config('services.manco.rust_base')."/api/chapters/{$this->id}/pages");
            if ($response->successful()) {
                $pages = $response->json('data') ?? [];
                
                // Dynamically rewrite short-lived/blocked domains and wrap them in our secure Image Proxy
                foreach ($pages as &$page) {
                    if (isset($page['image_url'])) {
                        // 1. Rewrite to persistent CDN first if it's a MangaDex URL
                        $persistentUrl = preg_replace('/https:\/\/[^\/]+\/data\//', 'https://uploads.mangadex.org/data/', $page['image_url']);
                        
                        // 2. Wrap in our secure local Image Proxy to bypass CORS and ISP blocks
                        $page['image_url'] = route('frontend.image-proxy', ['url' => base64_encode($persistentUrl)]);
                    }
                }
                unset($page);
                
                return $pages;
            }
        } catch (\Exception $e) {
            // Log or fallback
            logger()->error("Failed to fetch chapter pages from Rust: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Helper to save pages directly to NoSQL via the Rust backend API.
     */
    public function syncPagesToNoSql(array $pages): bool
    {
        try {
            $response = Http::timeout(5)->post(config('services.manco.rust_base')."/api/chapters/{$this->id}/pages", [
                'pages' => $pages,
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            logger()->error("Failed to sync pages to Rust: " . $e->getMessage());
            return false;
        }
    }
}
