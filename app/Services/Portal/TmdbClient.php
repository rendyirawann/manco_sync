<?php

namespace App\Services\Portal;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Film/TV: metadata from TMDB (rock-solid), stream resolution from the
 * self-hosted TMDB-Embed-API (Inside4ndroid) at services.manco.embed_base.
 * TMDB never breaks; only the stream step depends on the local Node service.
 */
class TmdbClient
{
    protected string $key;
    protected string $embed;
    protected string $img = 'https://image.tmdb.org/t/p/w500';

    public function __construct()
    {
        $this->key   = (string) config('services.manco.tmdb_key');
        $this->embed = rtrim((string) config('services.manco.embed_base'), '/');
    }

    public function hasKey(): bool
    {
        return trim($this->key) !== '';
    }

    protected function tmdb(string $path, array $query = [], int $ttl = 600): ?array
    {
        if (!$this->hasKey()) {
            return null;
        }
        $query['api_key'] = $this->key;
        $ck = 'tmdb:' . md5($path . '?' . http_build_query($query));

        // Stale-while-revalidate: instant cached serve + background refresh (see SankaClient::json).
        return Cache::flexible($ck, [$ttl, 86400], function () use ($path, $query) {
            try {
                $r = Http::timeout(10)->get('https://api.themoviedb.org/3' . $path, $query);
                if ($r->successful()) {
                    $b = $r->json();
                    if (is_array($b) && $b !== []) {
                        return $b;
                    }
                }
            } catch (\Throwable $e) {
                logger()->warning('TMDB failed [' . $path . ']: ' . $e->getMessage());
            }
            return null;
        });
    }

    /** results[] -> normalized cards ['id','type','title','poster','meta']. */
    public function cards(?array $body, string $type): array
    {
        $out = [];
        foreach (($body['results'] ?? []) as $m) {
            $t = $type ?: ($m['media_type'] ?? 'movie');
            if (!in_array($t, ['movie', 'tv'], true)) {
                continue;
            }
            $out[] = [
                'id'     => $m['id'] ?? null,
                'type'   => $t,
                'title'  => $m['title'] ?? $m['name'] ?? 'Untitled',
                'poster' => !empty($m['poster_path']) ? $this->img . $m['poster_path'] : '',
                'meta'   => substr((string) ($m['release_date'] ?? $m['first_air_date'] ?? ''), 0, 4)
                    . (!empty($m['vote_average']) ? ' · ★' . number_format($m['vote_average'], 1) : ''),
            ];
        }
        return array_values(array_filter($out, fn ($c) => $c['id']));
    }

    public function popular(string $type, int $page = 1): array
    {
        return $this->cards($this->tmdb("/$type/popular", ['page' => $page]), $type);
    }

    public function trending(string $type, int $page = 1): array
    {
        return $this->cards($this->tmdb("/trending/$type/week", ['page' => $page]), $type);
    }

    public function topRated(string $type, int $page = 1): array
    {
        return $this->cards($this->tmdb("/$type/top_rated", ['page' => $page]), $type);
    }

    public function search(string $type, string $q, int $page = 1): array
    {
        if (trim($q) === '') {
            return [];
        }
        return $this->cards($this->tmdb("/search/$type", ['query' => $q, 'page' => $page], 120), $type);
    }

    public function detail(string $type, string $id): ?array
    {
        $d = $this->tmdb("/$type/$id", ['append_to_response' => 'credits']);
        if (!$d || empty($d['id'])) {
            return null;
        }
        $meta = [];
        $year = substr((string) ($d['release_date'] ?? $d['first_air_date'] ?? ''), 0, 4);
        if ($year) { $meta[] = ['icon' => 'calendar', 'text' => $year]; }
        if (!empty($d['vote_average'])) { $meta[] = ['icon' => 'star', 'text' => number_format($d['vote_average'], 1)]; }
        if (!empty($d['runtime'])) { $meta[] = ['icon' => 'clock', 'text' => $d['runtime'] . ' min']; }
        if (!empty($d['number_of_seasons'])) { $meta[] = ['icon' => 'layer-group', 'text' => $d['number_of_seasons'] . ' Season']; }
        if (!empty($d['status'])) { $meta[] = ['icon' => 'signal', 'text' => $d['status']]; }

        $seasons = [];
        foreach (($d['seasons'] ?? []) as $s) {
            if (($s['season_number'] ?? 0) < 1) { continue; } // skip "Specials" (season 0)
            $seasons[] = ['number' => $s['season_number'], 'name' => $s['name'] ?? ('Season ' . $s['season_number']), 'episodes' => $s['episode_count'] ?? 0];
        }

        return [
            'id'       => $d['id'],
            'type'     => $type,
            'title'    => $d['title'] ?? $d['name'] ?? 'Detail',
            'poster'   => !empty($d['poster_path']) ? $this->img . $d['poster_path'] : '',
            'overview' => $d['overview'] ?? '',
            'genres'   => array_map(fn ($g) => $g['name'], $d['genres'] ?? []),
            'meta'     => $meta,
            'seasons'  => $seasons,
        ];
    }

    /** Episodes of a TV season. */
    public function tvEpisodes(string $id, int $season): array
    {
        $d = $this->tmdb("/tv/$id/season/$season");
        $out = [];
        foreach (($d['episodes'] ?? []) as $e) {
            $out[] = ['episode' => $e['episode_number'], 'name' => $e['name'] ?? ('Episode ' . $e['episode_number'])];
        }
        return $out;
    }

    /**
     * Resolve playable streams from the self-hosted TMDB-Embed-API.
     * @return array [ ['name','url','quality'], ... ]  (url = proxied m3u8/mp4)
     */
    public function streams(string $type, string $id, ?int $season = null, ?int $episode = null): array
    {
        $url = $type === 'tv'
            ? "{$this->embed}/api/streams/series/{$id}?season=" . ($season ?? 1) . '&episode=' . ($episode ?? 1)
            : "{$this->embed}/api/streams/movie/{$id}";
        try {
            $r = Http::timeout(30)->get($url);
            if ($r->successful()) {
                return $r->json('streams') ?? [];
            }
        } catch (\Throwable $e) {
            logger()->warning('Embed-API failed: ' . $e->getMessage());
        }
        return [];
    }
}
