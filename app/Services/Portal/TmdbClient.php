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

    /** Film/serial buatan Indonesia (negara asal ID), terpopuler dulu. */
    public function indonesian(string $type, int $page = 1): array
    {
        return $this->cards($this->tmdb("/discover/$type", [
            'with_origin_country' => 'ID',
            'sort_by'             => 'popularity.desc',
            'include_adult'       => 'false',
            'page'                => $page,
        ]), $type);
    }

    /**
     * Platform resmi di Indonesia tempat judul ini bisa ditonton (data TMDB/JustWatch).
     *
     * @return array{link: ?string, groups: array<string, array<int, array{name: string, logo: string}>>}
     */
    public function watchProviders(string $type, string $id): array
    {
        $d  = $this->tmdb("/$type/$id/watch/providers", [], 6 * 3600);
        $reg = $d['results']['ID'] ?? [];
        $labels = ['flatrate' => 'Langganan', 'free' => 'Gratis', 'ads' => 'Gratis (iklan)', 'rent' => 'Sewa', 'buy' => 'Beli'];
        $groups = [];
        foreach ($labels as $key => $label) {
            foreach (($reg[$key] ?? []) as $p) {
                $groups[$label][] = [
                    'name' => (string) ($p['provider_name'] ?? ''),
                    'logo' => !empty($p['logo_path']) ? 'https://image.tmdb.org/t/p/w92' . $p['logo_path'] : '',
                ];
            }
        }
        return ['link' => $reg['link'] ?? null, 'groups' => $groups];
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
        $d = $this->tmdb("/$type/$id", ['append_to_response' => 'credits,videos']);
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
            // Menit; untuk serial pakai durasi episode. Dipakai memilih subtitle yang lengkap.
            'runtime'  => (int) ($d['runtime'] ?? ($d['episode_run_time'][0] ?? 0)),
            'poster'   => !empty($d['poster_path']) ? $this->img . $d['poster_path'] : '',
            'overview' => $d['overview'] ?? '',
            'backdrop' => !empty($d['backdrop_path']) ? 'https://image.tmdb.org/t/p/w1280' . $d['backdrop_path'] : '',
            'tagline'  => $d['tagline'] ?? '',
            // Trailer YouTube resmi bila ada (prioritas: Trailer resmi > Trailer > Teaser).
            'trailer'  => (function () use ($d) {
                $v = collect($d['videos']['results'] ?? [])->where('site', 'YouTube');
                $pick = $v->where('type', 'Trailer')->where('official', true)->first()
                    ?? $v->where('type', 'Trailer')->first() ?? $v->where('type', 'Teaser')->first();
                return $pick['key'] ?? '';
            })(),
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
        // Penyedia di tmdb-embed-api bisa butuh puluhan detik; Octane memutus request
        // di 30 dtk (dulu halaman jadi 500 kosong). Batas 12 dtk, hasil berisi di-cache.
        $key = 'tmdb-streams:' . md5($url);
        if (is_array($hit = \Illuminate\Support\Facades\Cache::get($key))) {
            return $hit;
        }
        try {
            $r = Http::connectTimeout(2)->timeout(12)->get($url);
            if ($r->successful()) {
                $streams = $r->json('streams') ?? [];
                if ($streams) {
                    \Illuminate\Support\Facades\Cache::put($key, $streams, now()->addMinutes(20));
                }
                return $streams;
            }
        } catch (\Throwable $e) {
            logger()->warning('Embed-API failed: ' . $e->getMessage());
        }
        return [];
    }

    /**
     * Server iframe pihak ketiga yang menerima ID TMDB langsung. Dipakai sebagai
     * cadangan (dan saat ini satu-satunya sumber, karena tmdb-embed-api :8787
     * belum dipasang). Hanya penyedia yang terjangkau dari jaringan ID per
     * 4 Okt 2026; vidsrc.cc/.xyz, embed.su, moviesapi dan 111movies diblokir.
     */
    public function embeds(string $type, string $id, ?int $season = null, ?int $episode = null, ?string $subIdUrl = null): array
    {
        $s = $season ?? 1;
        $e = $episode ?? 1;
        $tv = $type === 'tv';
        // $subIdUrl = URL .vtt Indonesia milik kita (route portal.sub, CORS *).
        // Penyedia yang menerima subtitle eksternal memuatnya sebagai trek default,
        // jadi teks benar-benar tampil (bukan sekadar menu subtitle yang kosong).
        $q = fn (array $p) => '?' . http_build_query(array_filter($p, fn ($v) => $v !== null && $v !== ''));
        $sub = $subIdUrl ? ['sub_file' => $subIdUrl, 'sub_label' => 'Indonesia'] : [];
        $list = [
            // vidsrc.sh (keluarga vidsrc.me): ds_lang=id memilih sub Indonesia bawaan,
            // sub_url menambah file kita sebagai trek yang langsung aktif.
            ['VidSrc.sh (Sub Indo)', ($tv ? "https://vidsrc.sh/embed/tv/{$id}/{$s}/{$e}" : "https://vidsrc.sh/embed/movie/{$id}")
                . $q(['ds_lang' => 'id', 'sub_url' => $subIdUrl, 'sub_label' => $subIdUrl ? 'Indonesia' : null, 'sub_lang' => $subIdUrl ? 'id' : null]), 'id'],
            ['VidLink' . ($sub ? ' (Sub Indo)' : ''), ($tv ? "https://vidlink.pro/tv/{$id}/{$s}/{$e}" : "https://vidlink.pro/movie/{$id}") . ($sub ? $q($sub) : ''), $sub ? 'id' : null],
            ['VidSrc' . ($sub ? ' (Sub Indo)' : ''), ($tv ? "https://vidsrc.to/embed/tv/{$id}/{$s}/{$e}" : "https://vidsrc.to/embed/movie/{$id}") . ($sub ? $q($sub) : ''), $sub ? 'id' : null],
            ['Videasy', $tv ? "https://player.videasy.net/tv/{$id}/{$s}/{$e}" : "https://player.videasy.net/movie/{$id}", null],
            ['VidFast', $tv ? "https://vidfast.pro/tv/{$id}/{$s}/{$e}" : "https://vidfast.pro/movie/{$id}", null],
            ['2Embed', $tv ? "https://www.2embed.cc/embedtv/{$id}&s={$s}&e={$e}" : "https://www.2embed.cc/embed/{$id}", null],
            ['MultiEmbed', $tv ? "https://multiembed.mov/?video_id={$id}&tmdb=1&s={$s}&e={$e}" : "https://multiembed.mov/?video_id={$id}&tmdb=1", null],
            ['AutoEmbed', $tv ? "https://autoembed.co/tv/tmdb/{$id}-{$s}-{$e}" : "https://autoembed.co/movie/tmdb/{$id}", null],
        ];
        $out = [];
        foreach ($list as [$name, $url, $lang]) {
            $row = ['name' => $name, 'url' => $url, 'embed' => true];
            if ($lang) $row['lang'] = $lang;
            $out[] = $row;
        }
        return $out;
    }
}
