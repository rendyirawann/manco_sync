<?php

namespace App\Services\Portal;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Thin HTTP client for the Sanka Vollerei API (backs both anime & comic).
 * Live-proxy pattern: fetch on demand, cache the decoded body, never persist to DB.
 * Returns the FULL decoded JSON body (envelopes differ: anime uses {ok,data},
 * comic returns top-level keys) — callers pick what they need.
 */
class SankaClient
{
    protected string $base;
    protected string $ua;

    public function __construct()
    {
        $this->base = rtrim((string) config('services.manco.sanka_base'), '/');
        $this->ua   = (string) config('services.manco.scraper_ua');
    }

    /**
     * GET a path and return the decoded JSON body (array) or null on failure.
     *
     * Stale-while-revalidate (Cache::flexible): within $ttl the cached body is
     * served fresh; up to 24h old it's still served INSTANTLY as stale while a
     * refresh runs in the background (after the response is sent) — so after the
     * first load a page is never synchronously slow again, even after hours idle.
     * A failed fetch returns null, which flexible never treats as "fresh", so it
     * simply retries next call (no poisoning). Timeout is tight (8s, no retry) so
     * a dead source can't hang the page ~30s.
     */
    public function json(string $path, int $ttl = 300): ?array
    {
        $key = 'sanka:' . md5($path);

        return Cache::flexible($key, [$ttl, 86400], function () use ($path) {
            try {
                $res = Http::withHeaders([
                    'User-Agent' => $this->ua,
                    'Accept'     => 'application/json',
                ])->timeout(8)->get($this->base . $path);

                if ($res->successful()) {
                    $body = $res->json();
                    if (is_array($body) && $body !== []) {
                        return $body;
                    }
                }
            } catch (\Throwable $e) {
                logger()->warning("Sanka API failed [{$path}]: " . $e->getMessage());
            }

            return null; // not cached as fresh → retried on the next request
        });
    }
}
