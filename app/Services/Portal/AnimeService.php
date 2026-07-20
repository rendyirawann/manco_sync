<?php

namespace App\Services\Portal;

/**
 * Anime (Otakudesu scraper via Sanka). Clean {ok, data} envelope.
 * Endpoints: /anime/home, /anime/ongoing-anime, /anime/complete-anime,
 * /anime/anime/{id} (detail), /anime/episode/{id}, /anime/server/{id},
 * /anime/search/{q}, /anime/genre/{id}.
 */
class AnimeService
{
    public function __construct(protected SankaClient $client) {}

    /** Unwrap the {ok, data} envelope. */
    protected function data(string $path, int $ttl): ?array
    {
        $body = $this->client->json($path, $ttl);
        if ($body && ($body['ok'] ?? false)) {
            return $body['data'] ?? null;
        }
        return null;
    }

    public function home(int $ttl = 300): ?array
    {
        return $this->data('/anime/home', $ttl);
    }

    public function ongoing(int $ttl = 300): array
    {
        return $this->data('/anime/ongoing-anime', $ttl)['animeList'] ?? [];
    }

    public function completed(int $ttl = 900): array
    {
        return $this->data('/anime/complete-anime', $ttl)['animeList'] ?? [];
    }

    public function detail(string $id, int $ttl = 600): ?array
    {
        return $this->data('/anime/anime/' . trim($id, '/'), $ttl);
    }

    public function episode(string $id, int $ttl = 600): ?array
    {
        return $this->data('/anime/episode/' . trim($id, '/'), $ttl);
    }

    /** Resolve a streaming server id to its embed URL. */
    public function server(string $id, int $ttl = 600): ?array
    {
        return $this->data('/anime/server/' . trim($id, '/'), $ttl);
    }

    public function search(string $q, int $ttl = 120): array
    {
        if (trim($q) === '') {
            return [];
        }
        return $this->data('/anime/search/' . rawurlencode($q), $ttl)['animeList'] ?? [];
    }

    public function genre(string $id, int $ttl = 300): array
    {
        return $this->data('/anime/genre/' . trim($id, '/'), $ttl)['animeList'] ?? [];
    }
}
