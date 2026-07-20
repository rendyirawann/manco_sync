<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Manga;
use App\Models\Genre;
use App\Models\Chapter;
use Illuminate\Support\Str;

class MangaDummySeeder extends Seeder
{
    /**
     * Only seeds if there are NO manga in the database yet.
     * Will NOT overwrite real imported data.
     */
    public function run(): void
    {
        // === SMART CHECK: Skip if seeder data already exists ===
        if (Manga::where('source', 'seeder')->count() > 0) {
            $this->command->info('✅ Database already has seeder manga data. Skipping dummy seeder.');
            return;
        }

        $this->command->info('🌱 Seeding dummy manga data...');

        // Ensure genres exist first
        $this->call(GenreSeeder::class);

        $genreMap = Genre::pluck('id', 'slug');

        // ============================================================
        // Dummy manga list with realistic data & public cover images
        // using picsum.photos with consistent seeds for stable images
        // ============================================================
        $mangas = [
            [
                'title'       => 'Shadow Monarch Rising',
                'type'        => 'manhwa',
                'status'      => 'ongoing',
                'author'      => 'Chugong',
                'artist'      => 'Dubu',
                'description' => 'Seorang pemuda lemah bangkit menjadi yang terkuat setelah mendapatkan kekuatan Shadow Monarch yang legendaris. Petualangan epiknya dimulai dari dungeon paling berbahaya di dunia.',
                'rating'      => 9.2,
                'year'        => 2022,
                'cover'       => 'https://picsum.photos/seed/shadow1/300/430',
                'genres'      => ['action', 'fantasy', 'supernatural'],
                'chapters'    => 180,
            ],
            [
                'title'       => 'Blade of the Forgotten God',
                'type'        => 'manga',
                'status'      => 'ongoing',
                'author'      => 'Tatsuki Fujimoto',
                'artist'      => 'Tatsuki Fujimoto',
                'description' => 'Seorang pemuda miskin yang hidup bersama iblis sebagai pemburu setan tiba-tiba terseret dalam konspirasi para dewa yang terlupakan.',
                'rating'      => 9.0,
                'year'        => 2021,
                'cover'       => 'https://picsum.photos/seed/blade2/300/430',
                'genres'      => ['action', 'supernatural', 'thriller'],
                'chapters'    => 142,
            ],
            [
                'title'       => 'Infinite Mage',
                'type'        => 'manhwa',
                'status'      => 'ongoing',
                'author'      => 'Jöhannes Cabal',
                'artist'      => 'Studio Mir',
                'description' => 'Di dunia di mana sihir adalah segalanya, seorang anak dari keluarga biasa berambisi menjadi penyihir terhebat sepanjang masa.',
                'rating'      => 8.7,
                'year'        => 2021,
                'cover'       => 'https://picsum.photos/seed/mage3/300/430',
                'genres'      => ['fantasy', 'adventure', 'action'],
                'chapters'    => 210,
            ],
            [
                'title'       => 'Dragon Emperor Returns',
                'type'        => 'manhua',
                'status'      => 'ongoing',
                'author'      => 'Xiao Zhan',
                'artist'      => 'Manhua Studio X',
                'description' => 'Kaisar naga yang mati di tangan pengkhianat kembali hidup ribuan tahun kemudian untuk membalas dendam dan merebut kembali takhtanya.',
                'rating'      => 8.5,
                'year'        => 2020,
                'cover'       => 'https://picsum.photos/seed/dragon4/300/430',
                'genres'      => ['action', 'fantasy', 'historical'],
                'chapters'    => 320,
            ],
            [
                'title'       => 'The Last Apocalypse',
                'type'        => 'manhwa',
                'status'      => 'ongoing',
                'author'      => 'Kim Duk',
                'artist'      => 'Park Seo',
                'description' => 'Dunia hancur, monster menyerang, dan satu-satunya harapan adalah seorang pemain game yang terpilih menjadi "Player" terakhir.',
                'rating'      => 8.8,
                'year'        => 2023,
                'cover'       => 'https://picsum.photos/seed/apocalypse5/300/430',
                'genres'      => ['action', 'sci-fi', 'thriller'],
                'chapters'    => 95,
            ],
            [
                'title'       => 'Moonlit Academy',
                'type'        => 'manga',
                'status'      => 'ongoing',
                'author'      => 'Kazune Kawahara',
                'artist'      => 'Aruko',
                'description' => 'Di sekolah elite yang penuh dengan anak-anak berbakat, seorang gadis biasa menemukan cinta pertamanya yang penuh drama dan kejutan.',
                'rating'      => 8.1,
                'year'        => 2022,
                'cover'       => 'https://picsum.photos/seed/moonlit6/300/430',
                'genres'      => ['romance', 'drama', 'slice-of-life'],
                'chapters'    => 67,
            ],
            [
                'title'       => 'Sword Saint Path',
                'type'        => 'manga',
                'status'      => 'completed',
                'author'      => 'Hiromu Arakawa',
                'artist'      => 'Hiromu Arakawa',
                'description' => 'Seorang pendekar pedang muda melakukan perjalanan melintasi benua untuk menemukan arti sejati dari kekuatan dan pengorbanan.',
                'rating'      => 9.1,
                'year'        => 2018,
                'cover'       => 'https://picsum.photos/seed/sword7/300/430',
                'genres'      => ['action', 'adventure', 'drama'],
                'chapters'    => 288,
            ],
            [
                'title'       => 'Reborn as the Demon King',
                'type'        => 'manhwa',
                'status'      => 'ongoing',
                'author'      => 'Lee Hyun',
                'artist'      => 'Kim Jung',
                'description' => 'Seorang hero yang dibunuh oleh partainya sendiri bereinkarnasi sebagai raja iblis, bertekad untuk membalas dendam dengan kekuatan baru yang luar biasa.',
                'rating'      => 8.9,
                'year'        => 2023,
                'cover'       => 'https://picsum.photos/seed/demon8/300/430',
                'genres'      => ['action', 'fantasy', 'isekai'],
                'chapters'    => 130,
            ],
            [
                'title'       => 'Galaxy Wanderer',
                'type'        => 'manga',
                'status'      => 'ongoing',
                'author'      => 'Naoki Urasawa',
                'artist'      => 'Naoki Urasawa',
                'description' => 'Petualangan antar galaksi seorang pilot muda yang mencari planet asal usul manusia yang hilang sejak 1000 tahun lalu.',
                'rating'      => 8.6,
                'year'        => 2021,
                'cover'       => 'https://picsum.photos/seed/galaxy9/300/430',
                'genres'      => ['sci-fi', 'adventure', 'mystery'],
                'chapters'    => 156,
            ],
            [
                'title'       => 'Crimson Detective',
                'type'        => 'manga',
                'status'      => 'ongoing',
                'author'      => 'Gosho Aoyama',
                'artist'      => 'Gosho Aoyama',
                'description' => 'Detektif genius berambut merah memecahkan kasus-kasus mustahil yang membawa ia lebih dalam ke dunia bawah tanah organisasi misterius.',
                'rating'      => 8.4,
                'year'        => 2020,
                'cover'       => 'https://picsum.photos/seed/crimson10/300/430',
                'genres'      => ['mystery', 'thriller', 'action'],
                'chapters'    => 205,
            ],
            [
                'title'       => 'Spirit Tamer Chronicles',
                'type'        => 'manhua',
                'status'      => 'completed',
                'author'      => 'Wei Zhiyuan',
                'artist'      => 'Manhua Art Team',
                'description' => 'Di dunia di mana roh bisa dijinakkan dan dijadikan senjata, seorang pemuda berbakat memulai perjalanan untuk menjadi Spirit Tamer terhebat.',
                'rating'      => 8.3,
                'year'        => 2019,
                'cover'       => 'https://picsum.photos/seed/spirit11/300/430',
                'genres'      => ['fantasy', 'adventure', 'supernatural'],
                'chapters'    => 412,
            ],
            [
                'title'       => 'My Beautiful Devil',
                'type'        => 'manga',
                'status'      => 'completed',
                'author'      => 'Arina Tanemura',
                'artist'      => 'Arina Tanemura',
                'description' => 'Gadis biasa jatuh cinta dengan iblis tampan yang ditugaskan untuk mengambil jiwanya, namun cinta sejati mengubah segalanya.',
                'rating'      => 8.0,
                'year'        => 2020,
                'cover'       => 'https://picsum.photos/seed/devil12/300/430',
                'genres'      => ['romance', 'supernatural', 'drama'],
                'chapters'    => 78,
            ],
            [
                'title'       => 'Titan Breaker',
                'type'        => 'manga',
                'status'      => 'hiatus',
                'author'      => 'Hajime Isayama',
                'artist'      => 'Hajime Isayama',
                'description' => 'Pasukan elit manusia terakhir berjuang melawan makhluk raksasa yang mengancam kepunahan, di balik tembok raksasa terakhir.',
                'rating'      => 9.3,
                'year'        => 2017,
                'cover'       => 'https://picsum.photos/seed/titan13/300/430',
                'genres'      => ['action', 'drama', 'thriller'],
                'chapters'    => 139,
            ],
            [
                'title'       => 'Zero Hour Hunter',
                'type'        => 'manhwa',
                'status'      => 'ongoing',
                'author'      => 'Park Taejun',
                'artist'      => 'Nam Kyungsub',
                'description' => 'Pemain F-rank misterius yang menyembunyikan kekuatan sejatinya memimpin dungeon attack paling berbahaya sendirian.',
                'rating'      => 8.7,
                'year'        => 2022,
                'cover'       => 'https://picsum.photos/seed/zero14/300/430',
                'genres'      => ['action', 'fantasy', 'supernatural'],
                'chapters'    => 112,
            ],
            [
                'title'       => 'Eternal Spring Dojo',
                'type'        => 'manga',
                'status'      => 'ongoing',
                'author'      => 'Yusuke Murata',
                'artist'      => 'Yusuke Murata',
                'description' => 'Di kota tempat semua orang memiliki kekuatan super, seorang pria botak yang berlatih hanya untuk hobi tiba-tiba menjadi pahlawan terkuat.',
                'rating'      => 9.4,
                'year'        => 2019,
                'cover'       => 'https://picsum.photos/seed/spring15/300/430',
                'genres'      => ['action', 'comedy', 'supernatural'],
                'chapters'    => 188,
            ],
            [
                'title'       => 'Celestial Warrior',
                'type'        => 'manhua',
                'status'      => 'ongoing',
                'author'      => 'Chen Wei',
                'artist'      => 'Dragon Art',
                'description' => 'Prajurit surgawi diturunkan ke bumi untuk mencari 7 mata dewa yang hilang sebelum neraka membuka pintunya.',
                'rating'      => 8.2,
                'year'        => 2021,
                'cover'       => 'https://picsum.photos/seed/celestial16/300/430',
                'genres'      => ['action', 'fantasy', 'adventure'],
                'chapters'    => 267,
            ],
            [
                'title'       => 'Dark Healer',
                'type'        => 'manhwa',
                'status'      => 'completed',
                'author'      => 'Shin KwangHo',
                'artist'      => 'Lim DalYoung',
                'description' => 'Healer yang dikhianati rekan-rekannya bangkit kembali sebagai dark class yang ditakuti seluruh guild dunia dungeon.',
                'rating'      => 8.9,
                'year'        => 2020,
                'cover'       => 'https://picsum.photos/seed/healer17/300/430',
                'genres'      => ['action', 'fantasy', 'drama'],
                'chapters'    => 94,
            ],
            [
                'title'       => 'Alchemy Master',
                'type'        => 'manhua',
                'status'      => 'ongoing',
                'author'      => 'Zhao Lei',
                'artist'      => 'Phoenix Studio',
                'description' => 'Seorang alchemist muda mewarisi resep ramuan legendaris dan berpetualang mencari bahan-bahan langka di seluruh penjuru dunia.',
                'rating'      => 8.1,
                'year'        => 2022,
                'cover'       => 'https://picsum.photos/seed/alchemy18/300/430',
                'genres'      => ['fantasy', 'adventure', 'action'],
                'chapters'    => 198,
            ],
        ];

        foreach ($mangas as $data) {
            $slug  = Str::slug($data['title']);
            $manga = Manga::firstOrCreate(
                ['slug' => $slug],
                [
                    'title'        => $data['title'],
                    'slug'         => $slug,
                    'description'  => $data['description'],
                    'cover_image'  => $data['cover'],  // External URL — cover_url accessor handles this
                    'status'       => $data['status'],
                    'type'         => $data['type'],
                    'author'       => $data['author'],
                    'artist'       => $data['artist'],
                    'release_year' => $data['year'],
                    'rating'       => $data['rating'],
                    'views_count'  => rand(1000, 150000),
                    'source'       => 'seeder',
                ]
            );

            // Attach genres
            $genreIds = [];
            foreach ($data['genres'] as $genreSlug) {
                if (isset($genreMap[$genreSlug])) {
                    $genreIds[] = $genreMap[$genreSlug];
                }
            }
            if ($genreIds) {
                $manga->genres()->syncWithoutDetaching($genreIds);
            }

            // Create chapters (only if none exist)
            if ($manga->chapters()->count() === 0) {
                $totalChapters = min($data['chapters'], 30); // Only create latest 30 as dummy
                $startFrom     = max(1, $data['chapters'] - $totalChapters + 1);

                for ($i = $startFrom; $i <= $data['chapters']; $i++) {
                    Chapter::create([
                        'manga_id'       => $manga->id,
                        'chapter_number' => (string) $i,
                        'title'          => $i % 10 === 0 ? "Chapter {$i} - Titik Balik" : "Chapter {$i}",
                        'slug'           => Str::slug($manga->title . '-chapter-' . $i),
                        'created_at'     => now()->subDays($data['chapters'] - $i + rand(0, 3)),
                        'updated_at'     => now()->subDays($data['chapters'] - $i),
                    ]);
                }
            }

            $this->command->line("  ✔ {$data['title']} ({$data['chapters']} chapters)");
        }

        $this->command->info('');
        $this->command->info('✅ Dummy data seeded successfully! Total: ' . count($mangas) . ' manga series.');
    }
}
