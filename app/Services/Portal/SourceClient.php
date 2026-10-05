<?php

namespace App\Services\Portal;

/**
 * Generic multi-source client for the Sanka Vollerei aggregator.
 * One code path serves every source (anime/donghua/drama/movie) by reading
 * the config/portal_sources.php registry and NORMALIZING the (wildly varying)
 * response shapes defensively:
 *   - list arrays live under different keys per source -> recursive finder
 *   - id field is animeId | slug | bookId | id
 *   - poster is poster | thumbnail | image | cover | coverWap
 *   - episode video is otakudesu-style (server.qualities + serverId) OR
 *     donghua-style (streaming.servers direct urls) OR a raw mp4/m3u8/embed.
 * Dead source -> methods return [] / null so the UI can say "coba sumber lain".
 */
class SourceClient
{
    public function __construct(protected SankaClient $sanka) {}

    /* ------------------------------------------------------------------ */
    /* Registry helpers                                                    */
    /* ------------------------------------------------------------------ */

    public function categories(): array
    {
        return array_map(fn ($c) => $this->withoutDisabled($c), config('portal_sources', []));
    }

    public function category(string $cat): ?array
    {
        $c = config("portal_sources.$cat");
        return $c ? $this->withoutDisabled($c) : null;
    }

    /**
     * Sumber bertanda 'disabled' (hulunya mati) disembunyikan dari pilihan sumber,
     * tetapi konfigurasinya tetap utuh di config/portal_sources.php — cukup hapus
     * tandanya bila hulunya pulih.
     */
    protected function withoutDisabled(array $cat): array
    {
        $cat['sources'] = array_filter($cat['sources'] ?? [], fn ($s) => empty($s['disabled']));
        return $cat;
    }

    public function source(string $cat, string $src): ?array
    {
        return config("portal_sources.$cat.sources.$src");
    }

    /** First source key of a category (default selection). */
    public function defaultSource(string $cat): ?string
    {
        return array_key_first($this->category($cat)['sources'] ?? []) ?: null;
    }

    protected function tpl(string $template, array $vars): string
    {
        foreach ($vars as $k => $v) {
            // Slug yang dari hulunya SUDAH ber-persen-encoding (Maid:
            // "osananajimi%e2%99%82-…") wajib di-encode sekali lagi; dikirim
            // apa adanya, hulu men-decode-nya menjadi "♂" dan menjawab 500.
            // Bila tautan kartunya diikuti browser, Laravel sudah men-decode id itu
            // menjadi "♂"; kembalikan dulu ke bentuk persen huruf kecil milik hulu.
            if ($k === 'id' && preg_match('/[^\x20-\x7e]/', (string) $v)) {
                $v = preg_replace_callback('/%[0-9A-F]{2}/', fn ($m) => strtolower($m[0]), rawurlencode((string) $v));
            }
            if ($k === 'id' && str_contains((string) $v, '%')) {
                $v = rawurlencode((string) $v);
            }
            $template = str_replace('{' . $k . '}', $v, $template);
        }
        return $template;
    }

    /**
     * Alamat lengkap untuk sebuah path sumber. Sumber yang dilayani API lain
     * (kunci 'base', mis. anime-api lokal) mendapat path absolut; sisanya
     * tetap relatif terhadap basis Sanka.
     */
    protected function at(?array $s, string $path): string
    {
        $base = $s['base'] ?? null;
        return $base ? rtrim((string) $base, '/') . $path : $path;
    }

    /**
     * Pecah "path#kunci": sebagian respons memuat beberapa daftar sekaligus
     * (anime-api /oploverz/home: latestRelease & popularToday), dan '#kunci'
     * memilih daftar mana yang ditampilkan.
     * @return array{0:string,1:?string}
     */
    protected function splitFragment(string $path): array
    {
        $pos = strpos($path, '#');
        return $pos === false ? [$path, null] : [substr($path, 0, $pos), substr($path, $pos + 1)];
    }

    protected function pickFragment($body, ?string $key)
    {
        if ($key === null || !is_array($body)) {
            return $body;
        }
        return $body['data'][$key] ?? $body[$key] ?? [];
    }

    /* ------------------------------------------------------------------ */
    /* Fetching                                                            */
    /* ------------------------------------------------------------------ */

    /** Fetch one list ("Ongoing"/"Latest"/…) for a source -> normalized cards. */
    public function list(string $cat, string $src, string $path): array
    {
        [$path, $frag] = $this->splitFragment($path);
        $body = $this->sanka->json($this->at($this->source($cat, $src), $path), 300);
        return $this->cards($this->pickFragment($body, $frag));
    }

    /**
     * Seperti list(), tetapi ikut membawa keterangan paginasi dari hulu.
     *
     * Dibutuhkan karena tombol "halaman berikutnya" sebelumnya ditampilkan hanya
     * berdasarkan "halaman ini ada isinya". Pada halaman terakhir mangasusuku
     * (halaman 12, 2 item) tombolnya tetap muncul, lalu halaman 13 yang kosong
     * ditampilkan sebagai "Sumber ini sedang tidak mengembalikan data" — menuduh
     * sumber yang sehat, padahal katalognya memang habis.
     *
     * has_next null berarti hulu tidak memberi keterangan yang bisa dipegang;
     * pemanggil boleh memakai dugaan lamanya.
     */
    public function listWithMeta(string $cat, string $src, string $path): array
    {
        [$path, $frag] = $this->splitFragment($path);
        $body  = $this->sanka->json($this->at($this->source($cat, $src), $path), 300);
        $items = $this->cards($this->pickFragment($body, $frag));

        $pg = is_array($body) ? ($body['pagination'] ?? ($body['data']['pagination'] ?? null)) : null;
        $hasNext = null;
        if (is_array($pg)) {
            foreach (['hasNextPage', 'has_next_page', 'hasNext'] as $k) {
                if (array_key_exists($k, $pg)) {
                    $hasNext = (bool) $pg[$k];
                    break;
                }
            }
            if ($hasNext === null && array_key_exists('nextPage', $pg)) {
                $hasNext = !($pg['nextPage'] === null || $pg['nextPage'] === '');
            }
        }

        // "Tidak ada halaman berikutnya" TIDAK dipercaya pada halaman yang penuh.
        // Endpoint /latest dan /popular mangasusuku menjawab hasNextPage=false
        // padahal isinya penuh 20 item; mempercayainya buta akan mematikan
        // paginasi sumber yang sebenarnya masih punya data. Halaman pendek
        // (kurang dari 10 item) barulah tanda halaman terakhir yang bisa dipegang.
        if ($hasNext === false && count($items) >= 10) {
            $hasNext = null;
        }

        return ['items' => $items, 'has_next' => $hasNext];
    }

    public function search(string $cat, string $src, string $q): array
    {
        $this->lastSearchWasLocal = false; // Octane: instans bisa dipakai ulang antarpermintaan
        $s = $this->source($cat, $src);
        if (!$s || empty($s['search']) || trim($q) === '') {
            return [];
        }
        // Sumber yang pencarian hulunya diketahui rusak ('search_broken') langsung
        // memakai pencarian lokal — tanpa menunggu hulu menggantung ±15 detik.
        if (empty($s['search_broken'])) {
            $path = $this->tpl($s['search'], ['q' => rawurlencode($q)]);
            $body = $this->sanka->json($this->at($s, $path), 120);
            $hits = $this->cards($body, !empty($s['id_from_title']));
            // Ada hasil dari hulu: pakai. Kosong ATAU gagal: tetap tampilkan judul
            // yang mirip dari daftar sumber ini, bukan halaman kosong.
            // Sebagian hulu (Kiryuu, Maid) mengabaikan kata kunci dan selalu
            // membalas daftar populer. Bila TIDAK satu pun hasil menyinggung kata
            // kunci, anggap pencariannya rusak. (Satu yang cocok cukup: judul
            // alternatif/terjemahan tetap lolos.)
            $needle = $this->fold($q);
            $words = array_values(array_filter(explode(' ', $needle), fn ($w) => mb_strlen($w) >= 2));
            $relevant = $hits && collect($hits)->contains(fn ($h) => $this->titleScore($h['title'], $needle, $words) >= 35);
            if ($relevant) {
                return $hits;
            }
        }
        return $this->localSearch($cat, $src, $q);
    }

    /**
     * Seberapa cocok sebuah judul dengan kata kunci (0–100). 100 sama persis,
     * 90 memuat frasa, 75 memuat semua kata, selebihnya ejaan mirip/sebagian.
     * Ambang wajar: 35.
     */
    protected function titleScore(string $title, string $needle, array $words): int
    {
        $t = $this->fold($title);
        if ($t === $needle) {
            return 100;
        }
        if (str_contains($t, $needle)) {
            return 90;
        }
        if ($words && count(array_filter($words, fn ($w) => str_contains($t, $w))) === count($words)) {
            return 75;
        }
        // Ejaan mirip: kata kunci dibandingkan dengan tiap kata judul.
        $best = 0;
        foreach (explode(' ', $t) as $tw) {
            foreach ($words ?: [$needle] as $w) {
                // Hanya kata yang panjangnya sebanding: "over" vs "love" atau
                // "leveling" vs "leaving" terlalu jauh untuk disebut mirip.
                if (mb_strlen($tw) < 4 || mb_strlen($w) < 4 || abs(mb_strlen($tw) - mb_strlen($w)) > 2) {
                    continue;
                }
                similar_text($w, $tw, $pct);
                $best = max($best, $pct);
            }
        }
        $partial = $words ? count(array_filter($words, fn ($w) => str_contains($t, $w))) / count($words) : 0;
        return (int) max($best >= 85 ? $best * 0.6 : 0, $partial * 60);
    }

    /** Ditandai true oleh search() bila hasil terakhir berasal dari pencarian lokal. */
    public bool $lastSearchWasLocal = false;

    /**
     * Pencarian cadangan: saring judul dari daftar-daftar sumber itu sendiri
     * (beberapa halaman tiap daftar), diurutkan dari yang paling mirip.
     *
     * Dipakai ketika endpoint pencarian hulu rusak (BacaKomik: 500 setelah 16 dtk,
     * Komikindo: 500). Tidak mencakup seluruh katalog — hanya yang tampil di daftar —
     * tetapi jauh lebih berguna daripada "tidak ada data".
     */
    public function localSearch(string $cat, string $src, string $q): array
    {
        $this->lastSearchWasLocal = true;
        $s = $this->source($cat, $src);
        $needle = $this->fold($q);
        if (!$s || $needle === '') {
            return [];
        }
        $words = array_values(array_filter(explode(' ', $needle), fn ($w) => mb_strlen($w) >= 2));

        $pool = [];
        foreach ($s['lists'] ?? [] as $tpl) {
            $pages = str_contains($tpl, '{p}') ? 3 : 1;
            for ($p = 1; $p <= $pages; $p++) {
                [$path, $frag] = $this->splitFragment(str_replace('{p}', (string) $p, $tpl));
                $items = $this->cards($this->pickFragment($this->sanka->json($this->at($s, $path), 600), $frag));
                if (!$items) {
                    break;
                }
                foreach ($items as $it) {
                    $pool[$it['id']] ??= $it;
                }
            }
        }

        $scored = [];
        foreach ($pool as $it) {
            $score = $this->titleScore($it['title'], $needle, $words);
            if ($score >= 35) {
                $scored[] = [$score, $it];
            }
        }
        usort($scored, fn ($a, $b) => $b[0] <=> $a[0]);

        return array_map(fn ($x) => $x[1], array_slice($scored, 0, 40));
    }

    /** Huruf kecil, tanpa tanda baca, spasi tunggal — untuk pencocokan judul. */
    protected function fold(string $s): string
    {
        $s = mb_strtolower(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $s = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);
        return trim((string) preg_replace('/\s+/', ' ', $s));
    }

    public function detail(string $cat, string $src, string $id): ?array
    {
        $s = $this->source($cat, $src);
        if (!$s || empty($s['detail'])) {
            return null;
        }
        // Daftar "Terbaru" sebagian sumber berisi EPISODE, bukan judul (Anime Indo:
        // "…-episode-12", Oploverz+: "…-episode-01-subtitle-indonesia"). Id judul
        // didapat dengan membuang akhiran episodenya (pola per sumber: 'detail_strip').
        $fetchId = trim($id, '/');
        if (!empty($s['detail_strip'])) {
            $fetchId = (string) preg_replace($s['detail_strip'], '', $fetchId) ?: $fetchId;
        }
        $body = $this->sanka->json($this->at($s, $this->tpl($s['detail'], ['id' => $fetchId])), 600);
        if (!$body) {
            return null;
        }
        $data = $this->normalizeDetail($body, $cat, $src, $fetchId);
        // Respons 200 yang ternyata kosong (tanpa judul dan episode) dianggap gagal,
        // supaya pemanggil bisa jatuh ke cadangan (mis. langsung memutar episode).
        if (($data['title'] ?? 'Detail') === 'Detail' && empty($data['episodes'])) {
            return null;
        }
        // Sources with a SEPARATE chapter-list endpoint (shinigami: detail has no
        // chapters) — fetch that list and populate the episodes.
        if (empty($data['episodes']) && !empty($s['chapters'])) {
            $chBody = $this->sanka->json($this->at($s, $this->tpl($s['chapters'], ['id' => $fetchId])), 600);
            $data['episodes'] = $this->normalizeChapterList($chBody);
        }
        return $data;
    }

    public function episode(string $cat, string $src, string $id): ?array
    {
        $s = $this->source($cat, $src);
        if (!$s || empty($s['episode'])) {
            return null;
        }
        $body = $this->sanka->json($this->at($s, $this->tpl($s['episode'], ['id' => trim($id, '/')])), 600);
        return $body ? $this->normalizeEpisode($body) : null;
    }

    /** Comic 'read' kind: fetch a chapter -> normalized {title, mangaTitle, images[], prev, next}. */
    public function chapter(string $cat, string $src, string $id): ?array
    {
        $s = $this->source($cat, $src);
        if (!$s || empty($s['chapter'])) {
            return null;
        }
        $body = $this->sanka->json($this->at($s, $this->tpl($s['chapter'], ['id' => trim($id, '/')])), 600);
        if (!$body) {
            return null;
        }
        $d = $this->unwrap($body);

        $images = $d['images'] ?? $d['chapter_images'] ?? $d['pages'] ?? [];
        if (!$images) { // find first list of url-ish entries anywhere
            foreach ($d as $v) {
                if (is_array($v) && array_is_list($v) && isset($v[0])) {
                    $probe = is_array($v[0]) ? ($v[0]['url'] ?? $v[0]['src'] ?? $v[0]['image'] ?? $v[0]['link'] ?? '') : $v[0];
                    if (is_string($probe) && str_starts_with($probe, 'http')) { $images = $v; break; }
                }
            }
        }
        // Entries may be plain URL strings OR objects like {url|src|image|link} (e.g. komikindo).
        $images = array_values(array_filter(array_map(function ($x) {
            if (is_string($x)) return $x;
            if (is_array($x)) return $x['url'] ?? $x['src'] ?? $x['image'] ?? $x['link'] ?? $x['file'] ?? null;
            return null;
        }, (array) $images), fn ($u) => is_string($u) && str_starts_with($u, 'http')));
        $nav  = $d['navigation'] ?? [];
        $prevRaw = $nav['previousChapter'] ?? $nav['prev'] ?? $nav['prev_slug'] ?? $d['prev_chapter'] ?? $d['prevChapter'] ?? null;
        $nextRaw = $nav['nextChapter'] ?? $nav['next'] ?? $nav['next_slug'] ?? $d['next_chapter'] ?? $d['nextChapter'] ?? null;
        if (is_array($prevRaw)) { $prevRaw = $prevRaw['chapter_id'] ?? $prevRaw['slug'] ?? $prevRaw['link'] ?? null; }
        if (is_array($nextRaw)) { $nextRaw = $nextRaw['chapter_id'] ?? $nextRaw['slug'] ?? $nextRaw['link'] ?? null; }

        $clean = fn ($x) => (is_string($x) && $x !== '') ? trim(basename(rtrim($x, '/')), '/') : null;
        $prev = $clean($prevRaw);
        $next = $clean($nextRaw);

        // Navigasi prev/next dari hulu memakai slug PENDEK di sumber 'manga_prefixed'
        // (Mangakita: "chapter-1.41335"), padahal endpoint bacanya hanya menerima
        // "{manga}-chapter-1". Tanpa ini, tombol "chapter berikutnya" berujung 404.
        if (($s['chapter_id'] ?? '') === 'manga_prefixed') {
            $mangaId = (string) preg_replace('/-chapter-.*$/i', '', trim($id, '/'));
            if ($mangaId !== '' && $mangaId !== trim($id, '/')) {
                $prev = $prev ? $this->normalizeChapterId($cat, $src, $mangaId, $prev) : null;
                $next = $next ? $this->normalizeChapterId($cat, $src, $mangaId, $next) : null;
            }
        }

        // Some sources return BROKEN read-nav (mangasusuku: "#/prev/", "#/next/") or none.
        // Derive prev/next from the ordered chapter list in the detail — accurate, no
        // overshoot. No-ops for sources whose chapter id isn't "{manga}-chapter-N".
        $bad = fn ($raw) => is_string($raw) && str_contains($raw, '#');
        if ($bad($prevRaw) || $bad($nextRaw) || (!$prev && !$next)) {
            [$dp, $dn] = $this->deriveChapterNav($cat, $src, $id);
            if ($dp !== null || $dn !== null) { $prev = $dp; $next = $dn; }
        }

        return [
            'title'      => (string) $this->pick($d, ['chapter_title', 'title', 'chapter'], 'Chapter'),
            'mangaTitle' => (string) $this->pick($d, ['manga_title', 'mangaTitle', 'title'], ''),
            'images'     => $images,
            'prev'       => $prev,
            'next'       => $next,
        ];
    }

    /**
     * Derive prev/next chapter ids from the ordered detail chapter list — for sources
     * whose per-chapter read-nav is broken/missing (mangasusuku returns "#/next/").
     * Assumes the chapter id encodes "{mangaSlug}-chapter-N" and the list is newest-first.
     * Returns [prevId|null, nextId|null]; [null,null] if it can't map (safe no-op).
     */
    /**
     * Full ordered chapter list for the manga a chapter belongs to — powers the
     * reader's chapter dropdown / shortcut buttons / breadcrumb. $mangaId may be
     * passed explicitly (from the detail link's ?m=), else it's derived from a
     * "{manga}-chapter-N" id. Empty items[] if it can't resolve (e.g. UUID id, no ?m=).
     * @return array{mangaId:string,mangaTitle:string,items:array<int,array{id:string,label:string}>}
     */
    /**
     * Bentuk id chapter yang benar-benar diterima endpoint sumbernya.
     *
     * Sebagian sumber memberi slug PENDEK di daftar chapter (Kiryuu:
     * "chapter-1.375182") padahal endpoint bacanya hanya menerima slug PENUH
     * ("{manga}-chapter-1"). Akhiran ".375182" adalah id internal, bukan bagian
     * dari slug.
     *
     * Diaktifkan per sumber lewat 'chapter_id' => 'manga_prefixed' di
     * config/portal_sources.php, jadi sumber lain tidak tersentuh sama sekali.
     */
    protected function normalizeChapterId(string $cat, string $src, string $mangaId, string $rawId): string
    {
        $id = trim($rawId, '/');
        $s  = $this->source($cat, $src);
        if (($s['chapter_id'] ?? '') !== 'manga_prefixed' || $id === '' || $mangaId === '') {
            return $id;
        }
        $id = (string) preg_replace('/\.\d+$/', '', $id);
        return str_starts_with($id, $mangaId . '-') ? $id : $mangaId . '-' . $id;
    }

    /**
     * Daftar episode untuk halaman tonton (dropdown, grid, Prev/Next).
     * Urutan dinaikkan berdasarkan nomor episode bila setiap label bernomor;
     * kalau tidak, urutan detail dipakai dan dibalik bila tampak terbaru-dulu.
     * Mengembalikan ['seriesId','seriesTitle','items'=>[[id,label]],'prev','next'].
     */
    public function episodeNavFor(string $cat, string $src, string $epId, string $seriesId = ''): array
    {
        $out = ['seriesId' => '', 'seriesTitle' => '', 'items' => [], 'prev' => null, 'next' => null];
        $seriesId = trim($seriesId, '/');
        if ($seriesId === '') {
            return $out;
        }
        $detail = $this->detail($cat, $src, $seriesId);
        if (!$detail || empty($detail['episodes'])) {
            return $out;
        }
        $items = array_values(array_filter(array_map(
            fn ($e) => ['id' => trim((string) ($e['id'] ?? ''), '/'), 'label' => (string) ($e['label'] ?? 'Episode')],
            $detail['episodes']
        ), fn ($i) => $i['id'] !== ''));

        $num = function (array $i): ?float {
            // Nomor dari label ("Episode 12", "Eps 3.5"), cadangan dari id ("...-episode-12").
            foreach ([$i['label'], $i['id']] as $t) {
                if (preg_match('/(?:episode|eps?|ep)[\s._-]*(\d+(?:\.\d+)?)/i', $t, $m)) {
                    return (float) $m[1];
                }
            }
            return preg_match('/^\D*(\d+(?:\.\d+)?)\D*$/', $i['label'], $m) ? (float) $m[1] : null;
        };
        $nums = array_map($num, $items);
        if (count($items) > 1 && !in_array(null, $nums, true)) {
            array_multisort($nums, SORT_ASC, SORT_NUMERIC, $items);
        } elseif (count($items) > 1 && $nums[0] !== null && end($nums) !== null && $nums[0] > end($nums)) {
            $items = array_reverse($items);
        }

        $cur = trim($epId, '/');
        foreach ($items as $i => $it) {
            if ($it['id'] === $cur) {
                $out['prev'] = $items[$i - 1]['id'] ?? null;
                $out['next'] = $items[$i + 1]['id'] ?? null;
                break;
            }
        }
        $out['seriesId'] = $seriesId;
        $out['seriesTitle'] = (string) ($detail['title'] ?? '');
        $out['items'] = $items;
        return $out;
    }

    public function chapterListFor(string $cat, string $src, string $chapterId, string $mangaId = ''): array
    {
        $empty = ['mangaId' => '', 'mangaTitle' => '', 'items' => []];
        $mangaId = trim($mangaId, '/');
        if ($mangaId === '') {
            $cur   = trim($chapterId, '/');
            $guess = preg_replace('/-chapter-.*$/i', '', $cur);
            if ($guess === '' || $guess === $cur) {
                return $empty;
            }
            $mangaId = $guess;
        }
        $detail = $this->detail($cat, $src, $mangaId);
        if (!$detail) {
            return $empty;
        }
        // Id dinormalisasi di SINI, di tempat daftar chapter dibentuk — karena
        // daftar inilah yang mengisi dropdown chapter di halaman baca.
        $items = array_map(
            fn ($e) => [
                'id'    => $this->normalizeChapterId($cat, $src, $mangaId, (string) ($e['id'] ?? '')),
                'label' => (string) ($e['label'] ?? 'Chapter'),
            ],
            $detail['episodes'] ?? []
        );
        return ['mangaId' => $mangaId, 'mangaTitle' => (string) ($detail['title'] ?? ''), 'items' => array_values(array_filter($items, fn ($i) => $i['id'] !== ''))];
    }

    /**
     * prev/next chapter ids from the ordered chapter list — for sources with broken/
     * missing read-nav (mangasusuku "#/next/"). List is newest-first. Returns [prev, next].
     */
    protected function deriveChapterNav(string $cat, string $src, string $id): array
    {
        $items = $this->chapterListFor($cat, $src, $id)['items'];
        if (!$items) {
            return [null, null];
        }
        $cur = trim($id, '/');
        $idx = null;
        foreach ($items as $i => $it) {
            if ($it['id'] === $cur) { $idx = $i; break; }
        }
        if ($idx === null) {
            return [null, null];
        }
        return [
            $idx < count($items) - 1 ? $items[$idx + 1]['id'] : null, // prev (older = higher index)
            $idx > 0 ? $items[$idx - 1]['id'] : null,                 // next (newer = lower index)
        ];
    }

    /**
     * Novel 'text' kind: fetch a chapter -> {title, novelTitle, paragraphs[], prev, next}.
     * The chapter body is external HTML — we normalize <br>/<p> to breaks then strip ALL
     * tags (XSS-safe), so the view renders escaped plain text with our own paragraphs.
     */
    public function chapterText(string $cat, string $src, string $id): ?array
    {
        $s = $this->source($cat, $src);
        if (!$s || empty($s['chapter'])) {
            return null;
        }
        $body = $this->sanka->json($this->at($s, $this->tpl($s['chapter'], ['id' => trim($id, '/')])), 600);
        if (!$body) {
            return null;
        }
        $d = $this->unwrap($body);

        $raw = (string) $this->pick($d, ['content', 'text', 'chapter_content', 'isi', 'body'], '');
        $raw = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $raw);
        $raw = preg_replace('#</\s*p\s*>#i', "\n\n", $raw);
        $plain = html_entity_decode(strip_tags((string) $raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $paragraphs = array_values(array_filter(array_map('trim', preg_split('/\n{2,}/', $plain)), fn ($p) => $p !== ''));

        $nav  = $d['navigation'] ?? [];
        $prev = $nav['prev_slug'] ?? $nav['prev'] ?? null;
        $next = $nav['next_slug'] ?? $nav['next'] ?? null;

        return [
            'title'      => (string) $this->pick($d, ['title', 'chapter_title', 'chapter'], 'Bab'),
            'novelTitle' => (string) $this->pick($d, ['novel_title', 'parent_title', 'mangaTitle', 'novel'], ''),
            'paragraphs' => $paragraphs,
            'prev'       => $prev ? trim((string) $prev, '/') : null,
            'next'       => $next ? trim((string) $next, '/') : null,
        ];
    }

    /** Otakudesu-family: resolve a serverId to an embed URL. */
    public function serverResolve(string $cat, string $src, string $serverId): ?string
    {
        $s = $this->source($cat, $src);
        if (!$s || empty($s['server'])) {
            return null;
        }
        $body = $this->sanka->json($this->tpl($s['server'], ['id' => trim($serverId, '/')]), 600);
        $d = $body['data'] ?? $body;
        return $d['url'] ?? $d['streamingUrl'] ?? (is_string($d) ? $d : null);
    }

    /* ------------------------------------------------------------------ */
    /* Normalizers                                                         */
    /* ------------------------------------------------------------------ */

    /** Recursively locate the first array-of-objects that looks like a content list. */
    protected function findList($node, int $depth = 0): ?array
    {
        if (!is_array($node) || $depth > 5) {
            return null;
        }
        if (array_is_list($node) && count($node) && is_array($node[0])) {
            $keys = array_keys($node[0]);
            foreach (['title', 'bookName', 'name', 'episode', 'episodeId', 'chapter'] as $marker) {
                if (in_array($marker, $keys, true)) {
                    return $node;
                }
            }
        }
        foreach ($node as $v) {
            if (is_array($v)) {
                $r = $this->findList($v, $depth + 1);
                if ($r) {
                    return $r;
                }
            }
        }
        return null;
    }

    protected function pick(array $a, array $keys, $default = '')
    {
        foreach ($keys as $k) {
            if (isset($a[$k]) && $a[$k] !== '' && $a[$k] !== null) {
                return $a[$k];
            }
        }
        return $default;
    }

    protected function idOf(array $item): ?string
    {
        // manga_id first: shinigami keys detail/chapters on its UUID manga_id, NOT its numeric id.
        $id = $this->pick($item, ['manga_id', 'mangaId', 'animeId', 'slug', 'bookId', 'id', 'episodeId', 'chapterId', 'seriesId', 'postId', 'contentId'], '');
        if ($id === '') {
            // Many sources only expose a link/href like "/manga/{slug}/" — derive the slug.
            $link = $this->pick($item, ['href', 'link', 'url', 'endpoint'], '');
            if ($link !== '') {
                $id = basename(rtrim((string) $link, '/'));
            }
        }
        return $id !== '' ? trim((string) $id, '/') : null;
    }

    /** Body -> list of normalized cards ['id','title','poster','meta']. */
    /**
     * @param bool $idFromTitle  Untuk hasil yang tidak membawa slug/link sama sekali
     *                           (search mangasusuku): turunkan id dari judul —
     *                           slug situs itu adalah Str::slug(judul).
     */
    public function cards($body, bool $idFromTitle = false): array
    {
        $list = $this->findList($body);
        if (!$list) {
            return [];
        }
        $out = [];
        foreach ($list as $it) {
            if (!is_array($it)) {
                continue;
            }
            // Parser hulu yang rusak (MancoMix/komiku per Okt 2026) membalas satu
            // kartu kosong {title:"Manga", slug:"", href:"/comic//"} untuk SEMUA
            // pencarian; id-nya jatuh ke "comic" lalu 404. Buang kartu tanpa
            // slug yang href-nya berujung segmen kosong.
            if (array_key_exists('slug', $it) && trim((string) $it['slug']) === ''
                && preg_match('#//$#', (string) ($it['href'] ?? ''))) {
                continue;
            }
            $id = $this->idOf($it);
            if (!$id && $idFromTitle && !empty($it['title'])) {
                $id = \Illuminate\Support\Str::slug((string) $it['title']);
            }
            if (!$id) {
                continue;
            }
            $out[] = [
                'id'     => $id,
                'title'  => (string) $this->pick($it, ['title', 'bookName', 'name'], 'Untitled'),
                'poster' => (string) $this->pick($it, ['poster', 'thumbnail', 'image', 'imageSrc', 'cover', 'coverWap', 'coverUrl'], ''),
                'meta'   => (string) $this->pick($it, ['episode', 'type', 'status', 'chapter', 'score', 'release_time'], ''),
                'kind'   => $this->comicKind($it),
            ];
        }
        return $out;
    }

    /**
     * Jenis komik dari data hulu: 'manga' | 'manhwa' | 'manhua' | null.
     * Sumber menyebutnya lewat `type` (BacaKomik, Komikindo, Mangakita, MangaDex)
     * atau negara asal `country_id`/`country` (Westmanga, Shinigami). MancoMix,
     * Kiryuu dan Maid tidak menyebut apa pun → null (tidak ditebak).
     */
    protected function comicKind(array $it): ?string
    {
        $t = strtolower(trim((string) ($it['type'] ?? $it['comic_type'] ?? $it['format'] ?? '')));
        foreach (['manhwa', 'manhua', 'manga'] as $k) {
            if ($t !== '' && str_contains($t, $k)) {
                return $k;
            }
        }
        $c = strtoupper(trim((string) ($it['country_id'] ?? $it['country'] ?? '')));
        return match ($c) {
            'JP', 'JA', 'JPN', 'JAPAN' => 'manga',
            'KR', 'KO', 'KOR', 'KOREA', 'SOUTH KOREA' => 'manhwa',
            'CN', 'ZH', 'CHN', 'CHINA', 'ZH-HK' => 'manhua',
            default => null,
        };
    }

    protected function unwrap($body): array
    {
        // Otakudesu/samehadaku wrap payload in `data`; others are top-level.
        if (isset($body['data']) && is_array($body['data'])) {
            // anime-api membungkus sekali lagi: {data: {details: {...}}}.
            if (count($body['data']) === 1 && isset($body['data']['details']) && is_array($body['data']['details'])) {
                return $body['data']['details'];
            }
            return $body['data'];
        }
        if (isset($body['detail']) && is_array($body['detail'])) {
            return $body['detail'];
        }
        if (isset($body['details']) && is_array($body['details'])) { // mangakita
            return $body['details'];
        }
        return is_array($body) ? $body : [];
    }

    public function normalizeDetail($body, string $cat, string $src, ?string $id = null): array
    {
        $d = $this->unwrap($body);
        if (!$id) {
            $id = $this->pick($d, ['animeId', 'slug', 'bookId', 'id'], '');
        }

        // synopsis: string, or otakudesu {paragraphs:[]}
        $syn = $this->pick($d, ['synopsis', 'introduction', 'description', 'sinopsis'], '');
        if (is_array($syn)) {
            $syn = implode("\n\n", $syn['paragraphs'] ?? array_filter($syn, 'is_string'));
        }

        // genres: [{title|name}] or [strings]
        $genres = [];
        foreach ((array) $this->pick($d, ['genreList', 'genres', 'genre'], []) as $g) {
            $genres[] = is_array($g) ? (string) $this->pick($g, ['title', 'name'], '') : (string) $g;
        }

        // meta chips from common scalar fields
        $meta = [];
        foreach (['score' => 'star', 'rating' => 'star', 'type' => 'tv', 'status' => 'signal',
                  'duration' => 'clock', 'studio' => 'building', 'studios' => 'building',
                  'released' => 'calendar', 'aired' => 'calendar', 'country' => 'globe',
                  'season' => 'leaf', 'episodes_count' => 'list-ol'] as $k => $icon) {
            if (isset($d[$k]) && is_scalar($d[$k]) && $d[$k] !== '') {
                $meta[] = ['icon' => $icon, 'text' => (string) $d[$k]];
            }
        }

        // episode list
        $rawEps = $this->pick($d, ['episodeList', 'episodes_list', 'episode_list', 'episodes', 'chapters', 'chapterList'], []);
        if (!is_array($rawEps) || !array_is_list($rawEps)) {
            $rawEps = $this->findList($d) ?: [];
        }
        $episodes = [];
        foreach ($rawEps as $e) {
            if (!is_array($e)) {
                continue;
            }
            $eid = $this->pick($e, ['episodeId', 'slug', 'chapterId', 'id', 'eps_slug'], '');
            if ($eid === '' && !empty($e['href'])) {
                // Oploverz+ (anime-api) hanya memberi tautan halaman episodenya.
                $eid = basename(rtrim((string) $e['href'], '/'));
            }
            if ($eid === '') {
                continue;
            }
            // Mangakita: detail chapter slug is "chapter-1188.384280" but the read
            // endpoint wants "{mangaSlug}-chapter-1188" (drop the ".postId" suffix).
            if ($src === 'mangakita' && $id) {
                $eid = trim((string) $id, '/') . '-' . preg_replace('/\..*$/', '', ltrim((string) $eid, '/'));
            }
            // skip genre-ish rows that slipped in (they have href to /genre)
            if (isset($e['href']) && str_contains((string) $e['href'], '/genre')) {
                continue;
            }
            $label = $this->pick($e, ['title', 'episode', 'chapter', 'name', 'eps_title'], '');
            if ($label === '' && isset($e['eps'])) {
                $label = 'Episode ' . $e['eps'];
            }
            if ($label === '' && $eid !== '') {
                $derived = $eid;
                if ($id !== '') {
                    $prefix = trim($id, '/');
                    if (str_starts_with($derived, $prefix)) {
                        $derived = substr($derived, strlen($prefix));
                    }
                }
                $derived = ltrim($derived, '-');
                if ($derived === '') {
                    $derived = $eid;
                }
                $label = ucwords(str_replace(['-', '_'], ' ', $derived));
            }
            $episodes[] = [
                // Kiryuu: id mentah "chapter-2.153258" ditolak hulu (500); normalisasi
                // yang sama dengan dropdown pembaca -> "magic-emperor-chapter-2".
                'id'    => $this->normalizeChapterId($cat, $src, (string) $id, (string) $eid),
                'label' => (string) ($label ?: 'Episode'),
                'date'  => (string) $this->pick($e, ['date', 'released', 'time_ago'], ''),
            ];
        }

        $episodes = $this->fixLeadingOutlier($episodes);

        return [
            'title'    => (string) $this->pick($d, ['title', 'bookName', 'name'], 'Detail'),
            'alt'      => (string) $this->pick($d, ['japanese', 'title_indonesian', 'alter_title', 'englishTitle'], ''),
            'poster'   => (string) $this->pick($d, ['poster', 'coverWap', 'image', 'imageSrc', 'thumbnail', 'cover'], ''),
            'synopsis' => (string) $syn,
            'genres'   => array_values(array_filter($genres)),
            'meta'     => $meta,
            'episodes' => $episodes,
            'id'       => $id,
        ];
    }

    /**
     * Daftar chapter Kiryuu terurut terbaru-dulu, KECUALI chapter 1 yang nyasar ke
     * urutan pertama ([1, 3862, 3861, …, 2]). Akibatnya chapter 1 tampil di atas
     * sebagai "terbaru" dan navigasi turunan (prev/next) salah arah.
     * Hanya memindahkan elemen pertama ke belakang bila jelas-jelas menyimpang;
     * daftar yang sudah rapi tidak tersentuh.
     */
    protected function fixLeadingOutlier(array $eps): array
    {
        if (count($eps) < 3) {
            return $eps;
        }
        $num = function ($e) {
            return preg_match('/(\d+(?:\.\d+)?)/', (string) ($e['label'] ?? ''), $m) ? (float) $m[1] : null;
        };
        [$a, $b, $c] = [$num($eps[0]), $num($eps[1]), $num($eps[2])];
        if ($a !== null && $b !== null && $c !== null && $a < $b && $b > $c && $a < $c) {
            $eps[] = array_shift($eps);
        }
        return $eps;
    }

    /** Normalize a SEPARATE chapter-list response (e.g. shinigami) into episodes[]. */
    protected function normalizeChapterList($body): array
    {
        $eps = [];
        foreach ($this->findChapterArray($body) as $e) {
            if (!is_array($e)) {
                continue;
            }
            $eid = $this->pick($e, ['chapter_id', 'chapterId', 'id', 'slug', 'episodeId'], '');
            if ($eid === '') {
                continue;
            }
            $num   = $this->pick($e, ['chapter_number', 'chapter', 'number'], '');
            $title = $this->pick($e, ['chapter_title', 'title', 'name'], '');
            $label = $title !== '' ? $title : ($num !== '' ? 'Chapter ' . $num : 'Chapter');
            $eps[] = [
                'id'    => trim((string) $eid, '/'),
                'label' => (string) $label,
                'date'  => (string) $this->pick($e, ['release_date', 'date', 'updated_at', 'time'], ''),
            ];
        }
        return $eps;
    }

    /** Recursively find the first array of chapter-like objects. */
    protected function findChapterArray($node, int $depth = 0): array
    {
        if (!is_array($node) || $depth > 5) {
            return [];
        }
        if (array_is_list($node) && isset($node[0]) && is_array($node[0])) {
            $keys = array_keys($node[0]);
            foreach (['chapter_id', 'chapterId', 'chapter_number', 'chapter_title'] as $m) {
                if (in_array($m, $keys, true)) {
                    return $node;
                }
            }
        }
        foreach ($node as $v) {
            if (is_array($v)) {
                $r = $this->findChapterArray($v, $depth + 1);
                if ($r) {
                    return $r;
                }
            }
        }
        return [];
    }

    /** Find any playable url anywhere (embed/mp4/m3u8) as a last resort. */
    protected function findVideo($node, int $depth = 0): ?string
    {
        if (!is_array($node) || $depth > 7) {
            return null;
        }
        foreach ($node as $k => $v) {
            if (is_string($v) && str_starts_with($v, 'http') &&
                (stripos($v, '.mp4') !== false || stripos($v, '.m3u8') !== false ||
                 (is_string($k) && preg_match('/url|stream|embed|src|iframe/i', $k)))) {
                return $v;
            }
        }
        foreach ($node as $v) {
            if (is_array($v)) {
                $r = $this->findVideo($v, $depth + 1);
                if ($r) {
                    return $r;
                }
            }
        }
        return null;
    }

    public function normalizeEpisode($body): array
    {
        $d = $this->unwrap($body);
        $servers = [];
        $defaultUrl = '';

        // --- Otakudesu family: server.qualities[].serverList[] (serverId needs resolve) ---
        if (isset($d['server']['qualities'])) {
            $defaultUrl = (string) ($d['defaultStreamingUrl'] ?? '');
            foreach ($d['server']['qualities'] as $q) {
                $qt = $q['title'] ?? '';
                foreach ($q['serverList'] ?? [] as $sv) {
                    if (!empty($sv['serverId'])) {
                        $servers[] = ['name' => trim(($sv['title'] ?? 'Server') . ' ' . $qt), 'url' => null, 'serverId' => $sv['serverId']];
                    }
                }
            }
        }

        // --- Donghua / generic embed family: streaming.servers[] (direct urls) ---
        if (isset($d['streaming'])) {
            $defaultUrl = $defaultUrl ?: (string) ($d['streaming']['main_url']['url'] ?? '');
            foreach ($d['streaming']['servers'] ?? [] as $sv) {
                if (!empty($sv['url'])) {
                    $servers[] = ['name' => $sv['name'] ?? 'Server', 'url' => $sv['url'], 'serverId' => null];
                }
            }
        }

        // --- Generic: a servers/serverList/sources array of {name,url} ---
        if (!$servers) {
            foreach (['servers', 'serverList', 'sources', 'stream', 'streams', 'links'] as $key) {
                $arr = $d[$key] ?? null;
                if (is_array($arr) && array_is_list($arr)) {
                    foreach ($arr as $sv) {
                        $u = is_array($sv) ? $this->pick($sv, ['url', 'link', 'file', 'src'], '') : (is_string($sv) ? $sv : '');
                        if ($u) {
                            $servers[] = ['name' => is_array($sv) ? (string) $this->pick($sv, ['name', 'title', 'label', 'quality'], 'Server') : 'Server', 'url' => $u, 'serverId' => null];
                        }
                    }
                    if ($servers) {
                        break;
                    }
                }
            }
        }

        if (!$defaultUrl) {
            $defaultUrl = $servers[0]['url'] ?? ($this->findVideo($d) ?? '');
        }

        // downloads: otakudesu downloadUrl.qualities[], donghua download_url{...}, generic
        $downloads = [];
        $dl = $d['downloadUrl']['qualities'] ?? null;
        if (is_array($dl)) {
            foreach ($dl as $q) {
                $links = [];
                foreach ($q['urls'] ?? [] as $u) {
                    $links[] = ['name' => $u['title'] ?? 'Link', 'url' => $u['url'] ?? '#'];
                }
                $downloads[] = ['quality' => trim(($q['title'] ?? '') . ' ' . ($q['size'] ?? '')), 'links' => $links];
            }
        }
        // anime-api (Oploverz+): download[{title: format, qualityList[{title, urlList[{title,url}]}]}]
        if (!$downloads && is_array($d['download'] ?? null) && array_is_list($d['download'])) {
            foreach ($d['download'] as $fmt) {
                foreach ((array) ($fmt['qualityList'] ?? []) as $q) {
                    $links = [];
                    foreach ((array) ($q['urlList'] ?? []) as $u) {
                        if (!empty($u['url'])) {
                            $links[] = ['name' => $u['title'] ?? 'Link', 'url' => $u['url']];
                        }
                    }
                    if ($links) {
                        $downloads[] = ['quality' => trim(($fmt['title'] ?? '') . ' ' . ($q['title'] ?? '')), 'links' => $links];
                    }
                }
            }
        }

        // prev/next
        $prev = $d['prevEpisode']['episodeId'] ?? $d['navigation']['previous']['slug'] ?? $d['navigation']['prev'] ?? null;
        $next = $d['nextEpisode']['episodeId'] ?? $d['navigation']['next']['slug'] ?? $d['navigation']['next'] ?? null;
        if (is_array($prev)) { $prev = $prev['slug'] ?? $prev['episodeId'] ?? null; }
        if (is_array($next)) { $next = $next['slug'] ?? $next['episodeId'] ?? null; }

        $type = (stripos($defaultUrl, '.mp4') !== false || stripos($defaultUrl, '.m3u8') !== false) ? 'video' : 'embed';

        return [
            'title'      => (string) $this->pick($d, ['title', 'episode'], 'Nonton'),
            'animeId'    => $this->pick($d, ['animeId', 'bookId', 'slug'], ''),
            'defaultUrl' => $defaultUrl,
            'defaultType' => $type,
            'servers'    => $servers,
            'downloads'  => $downloads,
            'prev'       => $prev ? trim((string) $prev, '/') : null,
            'next'       => $next ? trim((string) $next, '/') : null,
        ];
    }
}
