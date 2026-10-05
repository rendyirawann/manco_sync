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

    // Per Okt 2026 hulu memindahkan Nekopoi dari /anime/neko/* ke /anime/nekopoi/*;
    // alamat lama dijawab 403 "salah endpoint". Isi per judul ada di /episode/{slug},
    // dan /random sudah tidak ada.

    public function latest(int $page = 1): array
    {
        $b = $this->sanka->json('/anime/nekopoi/latest?page=' . max(1, $page), 300);
        return $this->cards($b['data'] ?? $b['results'] ?? []);
    }

    public function search(string $q): array
    {
        if (trim($q) === '') {
            return [];
        }
        $b = $this->sanka->json('/anime/nekopoi/search?q=' . rawurlencode($q), 120);
        return $this->cards($b['data'] ?? $b['results'] ?? []);
    }

    /** $url = tautan halaman nekopoi (dari kartu); slug-nya = segmen terakhir. */
    public function detail(string $url): ?array
    {
        $slug = basename(rtrim(parse_url($url, PHP_URL_PATH) ?: $url, '/'));
        if ($slug === '' || !preg_match('/^[a-z0-9-]+$/i', $slug)) {
            return null;
        }
        $b = $this->sanka->json('/anime/nekopoi/episode/' . $slug, 600);
        return $this->normDetail($b['data'] ?? $b);
    }

    /** Hulu tidak lagi punya /random: ambil judul acak dari halaman terbaru acak. */
    public function random(): ?array
    {
        $items = $this->latest(random_int(1, 50)) ?: $this->latest(1);
        if (!$items) {
            return null;
        }
        return $this->detail($items[array_rand($items)]['url']);
    }

    /** Defensive detail normalizer — collects any http links as downloads. */
    protected function normDetail($d): ?array
    {
        if (!is_array($d) || empty($d['title'])) {
            return null;
        }
        $downloads = [];
        $streams = [];
        // Bentuk baru: streams[{server,url}] + downloads[{quality, links[{host,url}]}].
        // Stream = halaman embed (DoodStream/playmogo /e/…) yang boleh di-iframe →
        // diputar langsung di halaman detail, bukan lagi tautan keluar.
        foreach ((array) ($d['streams'] ?? []) as $st) {
            $u = is_array($st) ? ($st['url'] ?? '') : '';
            if (is_string($u) && str_starts_with($u, 'http') && !str_contains($u, 'discord.com')) {
                $streams[] = ['name' => (string) ($st['server'] ?? 'Server ' . (count($streams) + 1)), 'url' => $u];
            }
        }
        if (isset($d['downloads'][0]['links'])) {
            foreach ($d['downloads'] as $q) {
                foreach ((array) ($q['links'] ?? []) as $l) {
                    if (!empty($l['url'])) {
                        $downloads[] = ['name' => trim(($q['quality'] ?? '') . ' · ' . ($l['host'] ?? 'Link'), ' ·'), 'url' => $l['url']];
                    }
                }
            }
        }
        $dl = $downloads ? [] : ($d['download'] ?? $d['downloads'] ?? $d['stream'] ?? $d['streaming'] ?? []);
        if (is_array($dl)) {
            array_walk_recursive($dl, function ($v, $k) use (&$downloads) {
                if (is_string($v) && str_starts_with($v, 'http')) {
                    $downloads[] = ['name' => is_string($k) ? $k : 'Link', 'url' => $v];
                }
            });
        }
        $genre = $d['genre'] ?? ($d['series']['name'] ?? '');
        if (is_array($genre)) {
            $genre = implode(', ', array_map(fn ($g) => is_array($g) ? ($g['name'] ?? '') : $g, $genre));
        }
        $syn = $d['synopsis'] ?? $d['description'] ?? '';
        if (is_array($syn)) {
            $syn = implode(' ', array_filter($syn, 'is_string'));
        }

        return [
            'title'     => $d['title'],
            'img'       => $d['img'] ?? $d['thumbnail'] ?? $d['cover'] ?? '',
            'synopsis'  => (string) $syn,
            'genre'     => (string) $genre,
            'streams'   => $streams,
            'meta'      => array_filter([
                'Producer' => $d['producer'] ?? '', 'Durasi' => $d['duration'] ?? '', 'Size' => $d['size'] ?? '',
            ]),
            'downloads' => $downloads,
        ];
    }
}
