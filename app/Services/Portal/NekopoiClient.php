<?php

namespace App\Services\Portal;

/**
 * Nekopoi (18+ adult) via Sanka /anime/neko/*. Different shape from other
 * sources (detail via ?url=). Source is frequently empty/down (domains rotate),
 * so everything degrades gracefully. Gated behind an 18+ confirmation.
 */
class NekopoiClient
{
    public function __construct(protected SankaClient $sanka) {}

    /** Normalize a browse list into cards ['title','thumb','url']. */
    protected function cards($list): array
    {
        if (!is_array($list)) {
            return [];
        }
        $out = [];
        foreach ($list as $it) {
            if (!is_array($it)) {
                continue;
            }
            $url = $it['url'] ?? $it['link'] ?? $it['href'] ?? '';
            if (!$url) {
                continue;
            }
            $out[] = [
                'title' => $it['title'] ?? $it['name'] ?? 'Untitled',
                'thumb' => $it['img'] ?? $it['thumbnail'] ?? $it['cover'] ?? $it['image'] ?? '',
                'url'   => $url,
            ];
        }
        return $out;
    }

    public function latest(): array
    {
        $b = $this->sanka->json('/anime/neko/latest', 300);
        return $this->cards($b['results'] ?? $b['data'] ?? []);
    }

    public function search(string $q): array
    {
        if (trim($q) === '') {
            return [];
        }
        $b = $this->sanka->json('/anime/neko/search/' . rawurlencode($q), 120);
        return $this->cards($b['results'] ?? $b['data'] ?? []);
    }

    public function detail(string $url): ?array
    {
        if ($url === '') {
            return null;
        }
        $b = $this->sanka->json('/anime/neko/get?url=' . urlencode($url), 600);
        return $this->normDetail($b['data'] ?? $b);
    }

    public function random(): ?array
    {
        $b = $this->sanka->json('/anime/neko/random', 60);
        return $this->normDetail($b['data'] ?? $b);
    }

    /** Defensive detail normalizer — collects any http links as downloads. */
    protected function normDetail($d): ?array
    {
        if (!is_array($d) || empty($d['title'])) {
            return null;
        }
        $downloads = [];
        $dl = $d['download'] ?? $d['downloads'] ?? $d['stream'] ?? $d['streaming'] ?? [];
        if (is_array($dl)) {
            array_walk_recursive($dl, function ($v, $k) use (&$downloads) {
                if (is_string($v) && str_starts_with($v, 'http')) {
                    $downloads[] = ['name' => is_string($k) ? $k : 'Link', 'url' => $v];
                }
            });
        }
        $genre = $d['genre'] ?? '';
        if (is_array($genre)) {
            $genre = implode(', ', array_map(fn ($g) => is_array($g) ? ($g['name'] ?? '') : $g, $genre));
        }
        $syn = $d['synopsis'] ?? '';
        if (is_array($syn)) {
            $syn = implode(' ', array_filter($syn, 'is_string'));
        }

        return [
            'title'     => $d['title'],
            'img'       => $d['img'] ?? $d['thumbnail'] ?? $d['cover'] ?? '',
            'synopsis'  => (string) $syn,
            'genre'     => (string) $genre,
            'meta'      => array_filter([
                'Producer' => $d['producer'] ?? '', 'Durasi' => $d['duration'] ?? '', 'Size' => $d['size'] ?? '',
            ]),
            'downloads' => $downloads,
        ];
    }
}
