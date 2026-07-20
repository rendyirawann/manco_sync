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
        return $body ? $this->normalizeDetail($body, $cat, $src, $id) : null;
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
        if (!$images) { // find first list-of-url-strings anywhere
            foreach ($d as $v) {
                if (is_array($v) && array_is_list($v) && isset($v[0]) && is_string($v[0]) && str_starts_with($v[0], 'http')) {
                    $images = $v;
                    break;
                }
            }
        }
        $nav  = $d['navigation'] ?? [];
        $prev = $nav['previousChapter'] ?? $nav['prev'] ?? $nav['prev_slug'] ?? null;
        $next = $nav['nextChapter'] ?? $nav['next'] ?? $nav['next_slug'] ?? null;
        if (is_array($prev)) { $prev = $prev['slug'] ?? $prev['link'] ?? null; }
        if (is_array($next)) { $next = $next['slug'] ?? $next['link'] ?? null; }

        return [
            'title'      => (string) $this->pick($d, ['chapter_title', 'title', 'chapter'], 'Chapter'),
            'mangaTitle' => (string) $this->pick($d, ['manga_title', 'mangaTitle', 'title'], ''),
            'images'     => array_values(array_filter((array) $images, 'is_string')),
            'prev'       => $prev ? trim(basename(rtrim((string) $prev, '/')), '/') : null,
            'next'       => $next ? trim(basename(rtrim((string) $next, '/')), '/') : null,
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
        $id = $this->pick($item, ['animeId', 'slug', 'bookId', 'id', 'episodeId', 'chapterId', 'seriesId', 'postId', 'contentId'], '');
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
