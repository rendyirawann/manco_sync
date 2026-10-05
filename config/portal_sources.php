<?php

/*
|--------------------------------------------------------------------------
| Portal content sources (Sanka Vollerei multi-source aggregator)
|--------------------------------------------------------------------------
| Routed by content type. Each category has interchangeable SOURCES; the UI
| shows a source selector + a source picker on the hub. Paths are relative to
| services.manco.sanka_base. `{p}` = page placeholder (pagination), `{q}` =
| query, `{id}` = slug/id. `kind`: 'video' (player) or 'read' (chapter images).
| Full endpoint reference: docs/sanka-api-catalog.md
*/

return [

    'anime' => [
        'label' => 'Anime', 'icon' => 'fa-tv', 'kind' => 'video',
        'sources' => [
            'otakudesu' => [
                'label' => 'Otakudesu', 'icon' => 'fa-star',
                'lists' => ['Ongoing' => '/anime/ongoing-anime?page={p}', 'Completed' => '/anime/complete-anime?page={p}'],
                'search' => '/anime/search/{q}', 'detail' => '/anime/anime/{id}',
                'episode' => '/anime/episode/{id}', 'server' => '/anime/server/{id}',
            ],
            'samehadaku' => [
                'label' => 'Samehadaku', 'icon' => 'fa-layer-group',
                'lists' => ['Ongoing' => '/anime/samehadaku/ongoing?page={p}', 'Completed' => '/anime/samehadaku/completed?page={p}', 'Populer' => '/anime/samehadaku/popular?page={p}'],
                'search' => '/anime/samehadaku/search?q={q}', 'detail' => '/anime/samehadaku/anime/{id}',
                'episode' => '/anime/samehadaku/episode/{id}', 'server' => '/anime/samehadaku/server/{id}',
            ],
            'animasu' => [
                'label' => 'Animasu', 'icon' => 'fa-dog',
                'lists' => ['Ongoing' => '/anime/animasu/ongoing?page={p}', 'Completed' => '/anime/animasu/completed?page={p}', 'Populer' => '/anime/animasu/popular?page={p}'],
                'search' => '/anime/animasu/search/{q}', 'detail' => '/anime/animasu/detail/{id}',
                'episode' => '/anime/animasu/episode/{id}',
                // Hulu mati per 4 Okt 2026 (daftar & pencarian selalu kosong). Disembunyikan dari pilihan
                // sumber, konfigurasinya tetap; hapus baris 'disabled' bila pulih.
                'disabled' => true,
            ],
            'oploverz' => [
                'label' => 'Oploverz', 'icon' => 'fa-bolt',
                'lists' => ['Ongoing' => '/anime/oploverz/ongoing?page={p}', 'Completed' => '/anime/oploverz/completed?page={p}'],
                'search' => '/anime/oploverz/search/{q}', 'detail' => '/anime/oploverz/anime/{id}',
                'episode' => '/anime/oploverz/episode/{id}',
                // Hulu mati per 4 Okt 2026 (500 setelah ±20 detik; digantikan Oploverz+ di bawah). Disembunyikan dari pilihan
                // sumber, konfigurasinya tetap; hapus baris 'disabled' bila pulih.
                'disabled' => true,
            ],
            // Oploverz lewat anime-api (github.com/yogasungkowo/anime-api) yang
            // dijalankan LOKAL — scraper sendiri, tidak bergantung pada Sanka.
            // Daftarnya diambil dari halaman utama karena /oploverz/anime (jelajah)
            // kosong di hulu; item "Terbaru" berupa episode, jadi id judulnya
            // diturunkan dengan detail_strip.
            'oploverzplus' => [
                'label' => 'Oploverz+', 'icon' => 'fa-bolt',
                'base' => env('MANCO_ANIME_API_BASE', 'http://127.0.0.1:8103'),
                // Ongoing/Completed/Populer berhalaman (?page=N, dukungan halaman
                // ditambahkan di salinan lokal anime-api); Terbaru & Populer Hari Ini
                // dari halaman utama, satu halaman saja.
                'lists' => [
                    'Ongoing'          => '/oploverz/anime?status=ongoing&order=update&page={p}',
                    'Terbaru'          => '/oploverz/home#latestRelease',
                    'Completed'        => '/oploverz/anime?status=completed&order=update&page={p}',
                    'Populer'          => '/oploverz/anime?order=popular&page={p}',
                    'Populer Hari Ini' => '/oploverz/home#popularToday',
                ],
                'search' => '/oploverz/search?q={q}', 'detail' => '/oploverz/anime/{id}',
                'episode' => '/oploverz/episode/{id}',
                'detail_strip' => '/-episode-.*$/',
            ],
            'anoboy' => [
                'label' => 'Anoboy', 'icon' => 'fa-ghost',
                'lists' => ['Terbaru' => '/anime/anoboy/home?page={p}', 'A-Z' => '/anime/anoboy/az-list?page={p}'],
                'search' => '/anime/anoboy/search/{q}', 'detail' => '/anime/anoboy/anime/{id}',
                'episode' => '/anime/anoboy/episode/{id}',
            ],
            'stream' => [
                'label' => 'Anime Indo', 'icon' => 'fa-clapperboard',
                'lists' => ['Terbaru' => '/anime/stream/latest/{p}', 'Populer' => '/anime/stream/popular', 'Movie' => '/anime/stream/movie/{p}'],
                'search' => '/anime/stream/search/{q}', 'detail' => '/anime/stream/anime/{id}',
                'episode' => '/anime/stream/episode/{id}',
                // Daftar Terbaru berisi episode ("…-episode-12"); halaman judulnya
                // ada di slug tanpa akhiran itu.
                'detail_strip' => '/-episode-[^-]+$/',
            ],
            'animekuindo' => [
                'label' => 'Animeku', 'icon' => 'fa-spa',
                'lists' => ['Latest' => '/anime/animekuindo/latest?page={p}', 'Populer' => '/anime/animekuindo/popular?page={p}', 'Movie' => '/anime/animekuindo/movie?page={p}'],
                'search' => '/anime/animekuindo/search/{q}', 'detail' => '/anime/animekuindo/detail/{id}',
                'episode' => '/anime/animekuindo/episode/{id}',
            ],
            'animekompi' => [
                'label' => 'Animekompi', 'icon' => 'fa-sailboat',
                'lists' => ['Terbaru' => '/anime/animekompi/terbaru?page={p}', 'Movie' => '/anime/animekompi/movie?page={p}'],
                'search' => '/anime/animekompi/search?q={q}', 'detail' => '/anime/animekompi/detail/{id}',
                'episode' => '/anime/animekompi/episode/{id}',
                // Hulu mati per 4 Okt 2026 (hulu menjawab 403 untuk semua permintaan). Disembunyikan dari pilihan
                // sumber, konfigurasinya tetap; hapus baris 'disabled' bila pulih.
                'disabled' => true,
            ],
        ],
    ],

    'donghua' => [
        'label' => 'Donghua', 'icon' => 'fa-dragon', 'kind' => 'video',
        'sources' => [
            'anichin' => [
                'label' => 'Anichin', 'icon' => 'fa-dragon',
                'lists' => ['Ongoing' => '/anime/donghua/ongoing/{p}', 'Completed' => '/anime/donghua/completed/{p}', 'Latest' => '/anime/donghua/latest/{p}'],
                'search' => '/anime/donghua/search/{q}/1', 'detail' => '/anime/donghua/detail/{id}',
                'episode' => '/anime/donghua/episode/{id}',
            ],
            'donghub' => [
                'label' => 'Donghub', 'icon' => 'fa-fire',
                'lists' => ['Latest' => '/anime/donghub/latest?page={p}', 'Populer' => '/anime/donghub/popular?page={p}', 'Movie' => '/anime/donghub/movie?page={p}'],
                'search' => '/anime/donghub/search/{q}', 'detail' => '/anime/donghub/detail/{id}',
                'episode' => '/anime/donghub/episode/{id}',
            ],
        ],
    ],

    'drama' => [
        'label' => 'Drama', 'icon' => 'fa-clapperboard', 'kind' => 'video',
        'sources' => [
            // Koleksi /movie/api/* Sanka menolak IP box ini (403), jadi drama dilayani
            // scraper DrakorKita di anime-api lokal (lihat MANCO_ANIME_API_BASE).
            'drakorkita' => [
                'label' => 'Drama Korea', 'icon' => 'fa-heart',
                'base' => env('MANCO_ANIME_API_BASE', 'http://127.0.0.1:8103'),
                'lists' => ['Drama Korea' => '/drakorkita/list?country=korea&page={p}'],
                'search' => '/drakorkita/search?q={q}', 'detail' => '/drakorkita/detail/{id}',
                'episode' => '/drakorkita/episode/{id}',
            ],
            'drakorkita_cn' => [
                'label' => 'Drama China', 'icon' => 'fa-dragon',
                'base' => env('MANCO_ANIME_API_BASE', 'http://127.0.0.1:8103'),
                'lists' => ['Drama China' => '/drakorkita/list?country=china&page={p}'],
                'search' => '/drakorkita/search?q={q}', 'detail' => '/drakorkita/detail/{id}',
                'episode' => '/drakorkita/episode/{id}',
            ],
            'nontondrakor' => [
                'label' => 'NontonDrakor', 'icon' => 'fa-clapperboard',
                'lists' => ['Drama Cina' => '/movie/api/nontondrakor/drachin?page={p}', 'Drakor' => '/movie/api/nontondrakor/drakor?page={p}', 'Latest' => '/movie/api/nontondrakor/latest?page={p}'],
                'search' => '/movie/api/nontondrakor/search?q={q}', 'detail' => '/movie/api/nontondrakor/detail?id={id}',
                'disabled' => true, // /movie/api/* 403 untuk IP box ini
            ],
            'drakor' => [
                'label' => 'Drakor', 'icon' => 'fa-heart',
                'lists' => ['Latest' => '/movie/api/drakor/latest?page={p}', 'Ongoing' => '/movie/api/drakor/ongoing?page={p}', 'Complete' => '/movie/api/drakor/complete?page={p}'],
                'search' => '/movie/api/drakor/search?q={q}', 'detail' => '/movie/api/drakor/detail/{id}',
                'disabled' => true, // /movie/api/* 403 untuk IP box ini
            ],
            'dramabox' => [
                'label' => 'DramaBox', 'icon' => 'fa-tv',
                'lists' => ['Trending' => '/movie/api/dramabox/trending', 'Latest' => '/movie/api/dramabox/latest?page={p}'],
                'search' => '/movie/api/dramabox/search?q={q}', 'detail' => '/movie/api/dramabox/detail?bookId={id}',
                'episode' => '/movie/api/dramabox/stream?bookId={id}&episode=1',
                'disabled' => true, // /movie/api/* 403 untuk IP box ini
            ],
        ],
    ],

    // NOTE: Film/Movie is handled by FilmController (TMDB metadata + TMDB-Embed-API),
    // NOT this Sanka registry — the /movie/api/* namespace is access-gated (403).

    'comic' => [
        'label' => 'Manga & Manhwa', 'icon' => 'fa-book-open', 'kind' => 'read',
        // ONLY KomikStation removed (user request); originals kept. Extra sources added
        // (komikindo/mangakita/shinigami verified reading; kiryuu/maid/meganei have
        // upstream limits — see docs & the shinigami separate-chapters handling).
        'sources' => [
            'main' => [
                'label' => 'MancoMix', 'icon' => 'fa-book-open',
                'lists' => ['Terbaru' => '/comic/terbaru?page={p}', 'Pustaka' => '/comic/pustaka/{p}', 'Populer' => '/comic/populer', 'Trending' => '/comic/trending'],
                'search' => '/comic/search?q={q}', 'detail' => '/comic/comic/{id}', 'chapter' => '/comic/chapter/{id}',
            ],
            'westmanga' => [
                'label' => 'Westmanga', 'icon' => 'fa-dragon',
                'lists' => ['Latest' => '/comic/westmanga/latest?page={p}', 'Popular' => '/comic/westmanga/popular?page={p}', 'Ongoing' => '/comic/westmanga/ongoing?page={p}'],
                'search' => '/comic/westmanga/search?q={q}', 'detail' => '/comic/westmanga/detail/{id}', 'chapter' => '/comic/westmanga/chapter/{id}',
            ],
            'bacakomik' => [
                'label' => 'BacaKomik', 'icon' => 'fa-book',
                // Hanya Latest yang menerima ?page= (Populer/Top: satu halaman saja).
                'lists' => ['Latest' => '/comic/bacakomik/latest?page={p}', 'Populer' => '/comic/bacakomik/populer', 'Top' => '/comic/bacakomik/top'],
                'search' => '/comic/bacakomik/search/{q}', 'detail' => '/comic/bacakomik/detail/{id}', 'chapter' => '/comic/bacakomik/chapter/{id}',
                // pencarian hulu: 500 setelah ±16 dtk (4 Okt 2026) -> cari di daftar judul sumber ini sendiri.
                'search_broken' => true,
            ],
            'softkomik' => [
                'label' => 'Softkomik', 'icon' => 'fa-feather',
                'lists' => ['Update' => '/comic/softkomik/update', 'Ongoing' => '/comic/softkomik/ongoing', 'Completed' => '/comic/softkomik/completed'],
                'search' => '/comic/softkomik/search?q={q}', 'detail' => '/comic/softkomik/detail/{id}', 'chapter' => '/comic/softkomik/chapter/{id}',
                // Hulu mati per 4 Okt 2026 (daftar kosong, pencarian 500). Disembunyikan dari pilihan
                // sumber, konfigurasinya tetap; hapus baris 'disabled' bila pulih.
                'disabled' => true,
            ],
            // MangaDex lewat anime-api lokal (API resmi, bukan scraping). Bab Indonesia
            // diutamakan, selebihnya Inggris bertanda [EN]. Judul berlisensi resmi
            // sering tanpa bab yang bisa dibaca (hanya tautan ke situs resmi).
            'mangadex' => [
                'label' => 'MangaDex', 'icon' => 'fa-globe',
                'base' => env('MANCO_ANIME_API_BASE', 'http://127.0.0.1:8103'),
                'lists' => [
                    'Update'  => '/mangadex/list?order=latest&page={p}',
                    'Populer' => '/mangadex/list?order=popular&page={p}',
                    'Baru'    => '/mangadex/list?order=new&page={p}',
                    'Manga'   => '/mangadex/list?order=popular&type=manga&page={p}',
                    'Manhwa'  => '/mangadex/list?order=popular&type=manhwa&page={p}',
                    'Manhua'  => '/mangadex/list?order=popular&type=manhua&page={p}',
                ],
                'search' => '/mangadex/search?q={q}', 'detail' => '/mangadex/detail/{id}', 'chapter' => '/mangadex/chapter/{id}',
                'proxy_poster' => true,
            ],
            'komikindo' => [
                'label' => 'Komikindo', 'icon' => 'fa-star',
                'lists' => ['Terbaru' => '/comic/komikindo/latest/{p}', 'Pustaka' => '/comic/komikindo/library?page={p}'],
                'search' => '/comic/komikindo/search/{q}/1', 'detail' => '/comic/komikindo/detail/{id}', 'chapter' => '/comic/komikindo/chapter/{id}',
                // pencarian hulu: 500 (4 Okt 2026) -> cari di daftar judul sumber ini sendiri.
                'search_broken' => true,
            ],
            'mangakita' => [
                'label' => 'Mangakita', 'icon' => 'fa-book',
                'lists' => ['Terbaru' => '/comic/mangakita/projects/{p}', 'Semua Manga' => '/comic/mangakita/daftar-manga/{p}'],
                'search' => '/comic/mangakita/search/{q}/1', 'detail' => '/comic/mangakita/detail/{id}', 'chapter' => '/comic/mangakita/chapter/{id}',
                // Navigasi prev/next dari hulu memakai slug pendek ("chapter-1.41335");
                // endpoint bacanya hanya menerima "{manga}-chapter-1".
                'chapter_id' => 'manga_prefixed',
            ],
            'shinigami' => [
                'label' => 'Shinigami', 'icon' => 'fa-skull',
                'lists' => ['Terbaru' => '/comic/shinigami/latest?page={p}', 'Populer' => '/comic/shinigami/popular?page={p}', 'Rekomendasi' => '/comic/shinigami/recommended?page={p}'],
                'search' => '/comic/shinigami/search/{q}', 'detail' => '/comic/shinigami/detail/{id}',
                // chapters live in a SEPARATE endpoint (detail has none); SourceClient merges it.
                'chapters' => '/comic/shinigami/chapters/{id}', 'chapter' => '/comic/shinigami/read/{id}',
            ],
            'kiryuu' => [
                'label' => 'Kiryuu', 'icon' => 'fa-bolt',
                'lists' => ['Trending' => '/comic/kiryuu/home', 'Mingguan' => '/comic/kiryuu/top-weekly', 'Terbaru' => '/comic/kiryuu/latest', 'Populer' => '/comic/kiryuu/popular'],
                'search' => '/comic/kiryuu/search/{q}/1', 'detail' => '/comic/kiryuu/manga/{id}', 'chapter' => '/comic/kiryuu/chapter/{id}',
                // Endpoint DETAIL Kiryuu memberi slug PENDEK ("chapter-1.375182"),
                // sedangkan endpoint CHAPTER-nya hanya menerima slug PENUH
                // ("{manga}-chapter-1"); akhiran ".375182" itu id internal.
                // Tanpa penanda ini, dropdown chapter mengirim bentuk pendek dan
                // hulunya menjawab 500, sementara tombol prev/next tetap jalan karena
                // memakai prevSlug/nextSlug dari respons chapter yang sudah penuh —
                // gejalanya membingungkan: dua navigasi di halaman yang sama, satu
                // jalan satu tidak.
                'chapter_id' => 'manga_prefixed',
            ],
            'maid' => [
                'label' => 'Maid', 'icon' => 'fa-broom',
                'lists' => ['Terbaru' => '/comic/maid/latest?page={p}'],
                'search' => '/comic/maid/search?q={q}', 'detail' => '/comic/maid/manga/{id}', 'chapter' => '/comic/maid/chapter/{id}',
            ],
            'meganei' => [
                'label' => 'Meganei (Batch)', 'icon' => 'fa-file-zipper',
                'lists' => ['Home' => '/comic/meganei/home/{p}', 'List' => '/comic/meganei/list?page={p}'],
                // batch-PDF only, no per-chapter read endpoint → browse/detail only.
                'search' => '/comic/meganei/search/{q}', 'detail' => '/comic/meganei/info/{id}',
                // Hulu mati per 4 Okt 2026 (semua endpoint 500). Disembunyikan dari pilihan
                // sumber, konfigurasinya tetap; hapus baris 'disabled' bila pulih.
                'disabled' => true,
            ],
        ],
    ],

    // 18+ anime (HentaiHaven lewat anime-api lokal). Stream = HLS langsung
    // (octopusmanifest.org), tanpa iframe — situsnya mengirim X-Frame-Options.
    // Hanya lewat area 18+ (/portal/dewasa); adult → wajib Superadmin.
    'anime18' => [
        'label' => 'Anime 18+', 'icon' => 'fa-fire', 'kind' => 'video', 'adult' => true,
        'sources' => [
            'hentaihaven' => [
                'label' => 'HentaiHaven', 'icon' => 'fa-fire',
                'base' => env('MANCO_ANIME_API_BASE', 'http://127.0.0.1:8103'),
                'lists' => ['Terbaru' => '/hentaihaven/list?page={p}'],
                'search' => '/hentaihaven/search?q={q}', 'detail' => '/hentaihaven/detail/{id}',
                'episode' => '/hentaihaven/episode/{id}',
            ],
        ],
    ],

    // 18+ manga — age-gated: StreamController blocks unless session('adult_ok').
    // Reached from the 18+ area (/portal/dewasa), NOT the main beranda nav.
    'comic18' => [
        'label' => 'Manga 18+', 'icon' => 'fa-fire', 'kind' => 'read', 'adult' => true,
        'sources' => [
            'mangasusuku' => [
                'label' => 'Mangasusuku', 'icon' => 'fa-fire',
                // Only /list/{p} carries item slugs; /latest & /popular items have no
                // slug/href so their cards can't navigate to detail — so use /list only.
                // 'Semua' (/list) hanya memuat 112 judul dan berhenti di halaman 12:
                // pada halaman itu hulu menjawab 2 item dengan hasNextPage=false.
                //
                // Jelajah A-Z lewat list-by-char adalah satu-satunya jalan menembus
                // batas itu. Paginasinya nyata (isi tiap halaman berbeda) dan itemnya
                // punya slug — berbeda dari /latest dan /popular, yang MENGABAIKAN
                // parameter halaman (isi halaman 1, 2, 5, dan 50 identik) sekaligus
                // tidak menyertakan slug sehingga kartunya tidak bisa dibuka.
                //
                // Sebagian huruf memang kosong di hulu (mis. X) dan tabnya akan
                // tampil kosong — itu benar apa adanya, dan lebih baik daripada
                // menyembunyikannya lalu ada judul yang tak terjangkau.
                'lists' => [
                    'Semua' => '/comic/mangasusuku/list/{p}',
                ] + array_combine(
                    array_map('strtoupper', range('a', 'z')),
                    array_map(fn ($c) => "/comic/mangasusuku/list-by-char/{$c}/{p}", range('a', 'z'))
                ),
                'search' => '/comic/mangasusuku/search/{q}/1', 'detail' => '/comic/mangasusuku/detail/{id}', 'chapter' => '/comic/mangasusuku/chapter/{id}',
                // Hasil search tidak membawa slug/link; slug situsnya = Str::slug(judul).
                'id_from_title' => true,
                // Posters live on mangasusuku.com which is ISP-blocked (Trust+Positif) in ID —
                // route them through the server-side image proxy so covers can load.
                'proxy_poster' => true,
            ],
        ],
    ],

    // Novel = TEXT content (kind 'text' → StreamController renders the text reader).
    // Only SakuraNovel: full flow verified (home/detail/read; chapter text at data.content).
    // Generic /novel/* is unusable (no read-content endpoint + JSON-rounded 64-bit novelId).
    'novel' => [
        'label' => 'Novel', 'icon' => 'fa-feather-pointed', 'kind' => 'text',
        'sources' => [
            'sakuranovel' => [
                'label' => 'SakuraNovel', 'icon' => 'fa-feather-pointed',
                'lists' => ['Terbaru' => '/novel/sakuranovel/home?page={p}', 'A-Z' => '/novel/sakuranovel/daftar-novel'],
                'search' => '/novel/sakuranovel/search?q={q}&page=1', 'detail' => '/novel/sakuranovel/detail/{id}', 'chapter' => '/novel/sakuranovel/read/{id}',
            ],
        ],
    ],

];
