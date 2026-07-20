<?php

namespace App\Services\Portal;

/**
 * Comic / manga / manhwa / manhua (Komiku scraper via Sanka).
 * The comic API has an inconsistent envelope (no {ok,data}); this service
 * NORMALIZES everything into a common card shape:
 *   ['title','slug','image','meta','type'].
 * Working endpoints: /comic/terbaru, /comic/populer, /comic/search?q=,
 * /comic/comic/{slug} (detail), /comic/chapter/{slug} (reader).
 */
class ComicService
{
    public function __construct(protected SankaClient $client) {}

    /** Pull the slug out of a link like "/manga/{slug}/" or "/detail-komik/{slug}/". */
    protected function slugFrom(array $c): string
    {
        if (!empty($c['slug'])) {
            return $c['slug'];
        }
        $link = $c['href'] ?? $c['link'] ?? '';
        return basename(rtrim($link, '/'));
    }

    /** Normalize a raw comic list item into a card. */
    protected function card(array $c): array
    {
        return [
            'title' => $c['title'] ?? 'Tanpa Judul',
            'slug'  => $this->slugFrom($c),
            'image' => $c['thumbnail'] ?? $c['image'] ?? '',
            'meta'  => $c['chapter'] ?? $c['type'] ?? ($c['genre'] ?? ''),
            'type'  => strtolower($c['type'] ?? ''),
        ];
    }

    /** @return array<int,array> list of normalized cards */
    protected function cards(?array $items): array
    {
        if (!is_array($items)) {
            return [];
        }
        $out = [];
        foreach ($items as $it) {
            if (is_array($it) && (isset($it['title']))) {
                $out[] = $this->card($it);
            }
        }
        return $out;
    }

    public function latest(int $ttl = 300): array
    {
        $body = $this->client->json('/comic/terbaru', $ttl);
        return $this->cards($body['comics'] ?? null);
    }

    public function popular(int $ttl = 600): array
    {
        $body = $this->client->json('/comic/populer', $ttl);
        // popular may come under 'comics' or 'data'
        return $this->cards($body['comics'] ?? $body['data'] ?? null);
    }

    public function search(string $q, int $ttl = 120): array
    {
        if (trim($q) === '') {
            return [];
        }
        $body = $this->client->json('/comic/search?q=' . rawurlencode($q), $ttl);
        return $this->cards($body['data'] ?? null);
    }

    /** Full detail incl. chapter list. Returns the raw body (view parses defensively). */
    public function detail(string $slug, int $ttl = 600): ?array
    {
        return $this->client->json('/comic/comic/' . trim($slug, '/'), $ttl);
    }

    /** Chapter reader: expected { images:[], chapters:[], navigation:{prev,next} }. */
    public function chapter(string $slug, int $ttl = 600): ?array
    {
        return $this->client->json('/comic/chapter/' . trim($slug, '/'), $ttl);
    }
}
