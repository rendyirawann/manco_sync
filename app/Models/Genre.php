<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    use HasUuids;

    protected $table = 'genres';

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get mangas connected to this genre.
     */
    public function mangas(): BelongsToMany
    {
        return $this->belongsToMany(Manga::class, 'manga_genre', 'genre_id', 'manga_id');
    }
}
