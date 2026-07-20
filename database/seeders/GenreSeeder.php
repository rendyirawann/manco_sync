<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Genre;
use Illuminate\Support\Str;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $genres = [
            'Action',
            'Adventure',
            'Comedy',
            'Drama',
            'Fantasy',
            'Romance',
            'Sci-Fi',
            'Slice of Life',
            'Supernatural',
            'Mystery',
            'Thriller',
            'Historical',
            'Isekai',
            'Horror',
            'Sports'
        ];

        foreach ($genres as $name) {
            Genre::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
