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
            ],
            'oploverz' => [
                'label' => 'Oploverz', 'icon' => 'fa-bolt',
                'lists' => ['Ongoing' => '/anime/oploverz/ongoing?page={p}', 'Completed' => '/anime/oploverz/completed?page={p}'],
                'search' => '/anime/oploverz/search/{q}', 'detail' => '/anime/oploverz/anime/{id}',
                'episode' => '/anime/oploverz/episode/{id}',
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
            'nontondrakor' => [
                'label' => 'NontonDrakor', 'icon' => 'fa-clapperboard',
                'lists' => ['Drama Cina' => '/movie/api/nontondrakor/drachin?page={p}', 'Drakor' => '/movie/api/nontondrakor/drakor?page={p}', 'Latest' => '/movie/api/nontondrakor/latest?page={p}'],
                'search' => '/movie/api/nontondrakor/search?q={q}', 'detail' => '/movie/api/nontondrakor/detail?id={id}',
            ],
            'drakor' => [
                'label' => 'Drakor', 'icon' => 'fa-heart',
                'lists' => ['Latest' => '/movie/api/drakor/latest?page={p}', 'Ongoing' => '/movie/api/drakor/ongoing?page={p}', 'Complete' => '/movie/api/drakor/complete?page={p}'],
                'search' => '/movie/api/drakor/search?q={q}', 'detail' => '/movie/api/drakor/detail/{id}',
            ],
            'dramabox' => [
                'label' => 'DramaBox', 'icon' => 'fa-tv',
                'lists' => ['Trending' => '/movie/api/dramabox/trending', 'Latest' => '/movie/api/dramabox/latest?page={p}'],
                'search' => '/movie/api/dramabox/search?q={q}', 'detail' => '/movie/api/dramabox/detail?bookId={id}',
                'episode' => '/movie/api/dramabox/stream?bookId={id}&episode=1',
            ],
        ],
    ],

    // NOTE: Film/Movie is handled by FilmController (TMDB metadata + TMDB-Embed-API),
    // NOT this Sanka registry — the /movie/api/* namespace is access-gated (403).

    'comic' => [
        'label' => 'Manga & Manhwa', 'icon' => 'fa-book-open', 'kind' => 'read',
        // Sanka restructured comic into per-source namespaces; only sources whose
        // full read chain (list -> detail -> chapter IMAGES) verified live are kept.
        // Dropped: komikstation/main/westmanga/bacakomik (dead namespaces),
        // maid (chapter returns lazy-load placeholder data-URIs, blank pages),
        // meganei (batch-PDF only, no chapter endpoint),
        // softkomik (chapter needs 2 path params + backend outage). Verified 2026-07-20.
        'sources' => [
            'komikindo' => [
                'label' => 'Komikindo', 'icon' => 'fa-book-open',
                'lists' => ['Terbaru' => '/comic/komikindo/latest/{p}', 'Pustaka' => '/comic/komikindo/library/{p}'],
                'search' => '/comic/komikindo/search/{q}/1', 'detail' => '/comic/komikindo/detail/{id}', 'chapter' => '/comic/komikindo/chapter/{id}',
            ],
            'mangakita' => [
                'label' => 'Mangakita', 'icon' => 'fa-book',
                'lists' => ['Terbaru' => '/comic/mangakita/projects/{p}', 'Semua Manga' => '/comic/mangakita/daftar-manga/{p}'],
                'search' => '/comic/mangakita/search/{q}/1', 'detail' => '/comic/mangakita/detail/{id}', 'chapter' => '/comic/mangakita/chapter/{id}',
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
                'lists' => ['Semua' => '/comic/mangasusuku/list/{p}'],
                'search' => '/comic/mangasusuku/search/{q}/1', 'detail' => '/comic/mangasusuku/detail/{id}', 'chapter' => '/comic/mangasusuku/chapter/{id}',
            ],
        ],
    ],

];
