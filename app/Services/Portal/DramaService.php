<?php

namespace App\Services\Portal;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Short-drama (Dracin / Anichin). Unified across 15 sources.
 * REQUIRES a paid X-API-Key (buy via Telegram @Anichin_Premium_Bot).
 * Without a key every call returns null and the UI shows a "key needed" state.
 * Endpoints: /{source}/trending, /foryou?page=, /search?query=, /detail?id=, /episode?id=&ep=.
 */
class DramaService
{
    protected string $base;
    protected string $key;
    protected string $ua;

    public function __construct()
    {
        $this->base = rtrim((string) config('services.manco.dracin_base'), '/');
        $this->key  = (string) config('services.manco.dracin_key');
        $this->ua   = (string) config('services.manco.scraper_ua');
    }

    public function hasKey(): bool
    {
        return trim($this->key) !== '';
    }

    /** All supported short-drama sources. */
    public function sources(): array
    {
        return ['dramabox', 'reelshort', 'shortmax', 'netshort', 'goodshort', 'dramawave',
            'flickreels', 'freereels', 'stardusttv', 'idrama', 'dramanova', 'starshort',
            'dramabite', 'melolo', 'moboreels'];
    }

    protected function get(string $path, array $query = [], int $ttl = 180): ?array
    {
        if (!$this->hasKey()) {
            return null;
        }
        $cacheKey = 'dracin:' . md5($path . '?' . http_build_query($query));
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        try {
            $res = Http::withHeaders([
                'User-Agent' => $this->ua,
                'X-API-Key'  => $this->key,
                'Accept'     => 'application/json',
            ])->timeout(15)->get($this->base . $path, $query);

            if ($res->successful()) {
                $body = $res->json();
                if (is_array($body)) {
                    Cache::put($cacheKey, $body, $ttl);
                    return $body;
                }
            } else {
                logger()->warning("Dracin API {$res->status()} [{$path}]");
            }
        } catch (\Throwable $e) {
            logger()->warning("Dracin API failed [{$path}]: " . $e->getMessage());
        }
        return null;
    }

    public function trending(string $source, int $ttl = 300): ?array
    {
        return $this->get("/{$source}/trending", [], $ttl);
    }

    public function foryou(string $source, int $page = 1, int $ttl = 180): ?array
    {
        return $this->get("/{$source}/foryou", ['page' => $page], $ttl);
    }

    public function search(string $source, string $q, int $ttl = 120): ?array
    {
        return $this->get("/{$source}/search", ['query' => $q], $ttl);
    }

    public function detail(string $source, string $id, int $ttl = 600): ?array
    {
        return $this->get("/{$source}/detail", ['id' => $id], $ttl);
    }

    public function episode(string $source, string $id, int $ep = 1, int $ttl = 600): ?array
    {
        return $this->get("/{$source}/episode", ['id' => $id, 'ep' => $ep], $ttl);
    }
}
