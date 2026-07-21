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
        return config('portal_sources', []);
    }

    public function category(string $cat): ?array
    {
        return config("portal_sources.$cat");
    }

    public function source(string $cat, string $src): ?array
    {
        return config("portal_sources.$cat.sources.$src");
    }

    /** First source key of a category (default selection). */
    public function defaultSource(string $cat): ?string
    {
        $srcs = config("portal_sources.$cat.sources", []);
        return array_key_first($srcs) ?: null;
    }

    protected function tpl(string $template, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $template = str_replace('{' . $k . '}', $v, $template);
        }
        return $template;
    }

    /* ------------------------------------------------------------------ */
    /* Fetching                                                            */
    /* ------------------------------------------------------------------ */

    /** Fetch one list ("Ongoing"/"Latest"/…) for a source -> normalized cards. */
    public function list(string $cat, string $src, string $path): array
    {
        $body = $this->sanka->json($path, 300);
        return $this->cards($body);
    }

    public function search(string $cat, string $src, string $q): array
    {
        $s = $this->source($cat, $src);
        if (!$s || empty($s['search']) || trim($q) === '') {
            return [];
        }
        $path = $this->tpl($s['search'], ['q' => rawurlencode($q)]);
        return $this->cards($this->sanka->json($path, 120));
    }

    public function detail(string $cat, string $src, string $id): ?array
    {
        $s = $this->source($cat, $src);
        if (!$s || empty($s['detail'])) {
            return null;
        }
        $body = $this->sanka->json($this->tpl($s['detail'], ['id' => trim($id, '/')]), 600);
        if (!$body) {
            return null;
        }
        $data = $this->normalizeDetail($body, $cat, $src, $id);
        // Sources with a SEPARATE chapter-list endpoint (shinigami: detail has no
        // chapters) — fetch that list and populate the episodes.
        if (empty($data['episodes']) && !empty($s['chapters'])) {
            $chBody = $this->sanka->json($this->tpl($s['chapters'], ['id' => trim($id, '/')]), 600);
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
        $body = $this->sanka->json($this->tpl($s['episode'], ['id' => trim($id, '/')]), 600);
        return $body ? $this->normalizeEpisode($body) : null;
    }

    /** Comic 'read' kind: fetch a chapter -> normalized {title, mangaTitle, images[], prev, next}. */
    public function chapter(string $cat, string $src, string $id): ?array
    {
        $s = $this->source($cat, $src);
        if (!$s || empty($s['chapter'])) {
            return null;
        }
        $body = $this->sanka->json($this->tpl($s['chapter'], ['id' => trim($id, '/')]), 600);
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
    protected function deriveChapterNav(string $cat, string $src, string $id): array
    {
        $cur = trim($id, '/');
        $mangaSlug = preg_replace('/-chapter-.*$/i', '', $cur);
        if ($mangaSlug === '' || $mangaSlug === $cur) {
            return [null, null]; // id doesn't look like "{manga}-chapter-N"
        }
        $detail = $this->detail($cat, $src, $mangaSlug);
        $eps = $detail['episodes'] ?? [];
        if (!$eps) {
            return [null, null];
        }
        $idx = null;
        foreach ($eps as $i => $e) {
            if (trim((string) ($e['id'] ?? ''), '/') === $cur) { $idx = $i; break; }
        }
        if ($idx === null) {
            return [null, null];
        }
        // List is newest-first (chapter N at a lower index): next = idx-1, prev = idx+1.
        $next = $idx > 0 ? trim((string) $eps[$idx - 1]['id'], '/') : null;
        $prev = $idx < count($eps) - 1 ? trim((string) $eps[$idx + 1]['id'], '/') : null;
        return [$prev, $next];
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
        $body = $this->sanka->json($this->tpl($s['chapter'], ['id' => trim($id, '/')]), 600);
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
    public function cards($body): array
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
            $id = $this->idOf($it);
            if (!$id) {
                continue;
            }
            $out[] = [
                'id'     => $id,
                'title'  => (string) $this->pick($it, ['title', 'bookName', 'name'], 'Untitled'),
                'poster' => (string) $this->pick($it, ['poster', 'thumbnail', 'image', 'cover', 'coverWap', 'coverUrl'], ''),
                'meta'   => (string) $this->pick($it, ['episode', 'type', 'status', 'chapter', 'score', 'release_time'], ''),
            ];
        }
        return $out;
    }

    protected function unwrap($body): array
    {
        // Otakudesu/samehadaku wrap payload in `data`; others are top-level.
        if (isset($body['data']) && is_array($body['data'])) {
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
        $rawEps = $this->pick($d, ['episodeList', 'episodes_list', 'episodes', 'chapters', 'chapterList'], []);
        if (!is_array($rawEps) || !array_is_list($rawEps)) {
            $rawEps = $this->findList($d) ?: [];
        }
        $episodes = [];
        foreach ($rawEps as $e) {
            if (!is_array($e)) {
                continue;
            }
            $eid = $this->pick($e, ['episodeId', 'slug', 'chapterId', 'id'], '');
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
            $label = $this->pick($e, ['title', 'episode', 'chapter', 'name'], '');
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
                'id'    => trim((string) $eid, '/'),
                'label' => (string) ($label ?: 'Episode'),
                'date'  => (string) $this->pick($e, ['date', 'released', 'time_ago'], ''),
            ];
        }

        return [
            'title'    => (string) $this->pick($d, ['title', 'bookName', 'name'], 'Detail'),
            'alt'      => (string) $this->pick($d, ['japanese', 'title_indonesian', 'alter_title', 'englishTitle'], ''),
            'poster'   => (string) $this->pick($d, ['poster', 'coverWap', 'image', 'thumbnail', 'cover'], ''),
            'synopsis' => (string) $syn,
            'genres'   => array_values(array_filter($genres)),
            'meta'     => $meta,
            'episodes' => $episodes,
            'id'       => $id,
        ];
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
            foreach (['servers', 'serverList', 'sources', 'stream', 'links'] as $key) {
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
