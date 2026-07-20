<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Manga extends Model
{
    use HasUuids;

    protected $table = 'mangas';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image',
        'status',
        'type',
        'author',
        'artist',
        'release_year',
        'rating',
        'views_count',
        'source',
        'language',
    ];

    protected $casts = [
        'rating'       => 'float',
        'views_count'  => 'integer',
        'release_year' => 'integer',
    ];

    protected $appends = ['cover_url', 'latest_chapter'];

    /**
     * Get chapters associated with this manga.
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class, 'manga_id', 'id')->orderBy('chapter_number', 'desc');
    }

    /**
     * Get genres associated with this manga.
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'manga_genre', 'manga_id', 'genre_id');
    }

    /**
     * Get the full public URL for the cover image.
     */
    public function getCoverUrlAttribute(): string
    {
        if (!$this->cover_image) {
            return asset('images/frontend/no-cover.jpg');
        }
        // If it's already a full URL (e.g. MangaDex CDN), return as-is
        if (str_starts_with($this->cover_image, 'http')) {
            return $this->cover_image;
        }
        return asset('storage/manga/covers/' . $this->cover_image);
    }

    /**
     * Get the latest chapter number for display.
     */
    public function getLatestChapterAttribute(): ?string
    {
        return $this->chapters()->max('chapter_number');
    }
}

