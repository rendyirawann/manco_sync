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
     * refresh runs in the background (after the response is sent).
     *
     * Kegagalan TIDAK boleh ditulis sebagai null. Pada jalur refresh latar,
     * flexible menulis hasil callback apa adanya — null akan MENIMPA salinan
     * stale yang masih bagus, sehingga request berikutnya harus fetch sinkron
     * dan bisa berakhir 404. Karena itu saat hulu gagal, callback mengembalikan
     * salinan lama (bila ada); null hanya bila memang belum pernah ada data.
     */
    public function json(string $path, int $ttl = 300): ?array
    {
        $key = 'sanka:' . md5($path);
        $failKey = 'sanka-fail:' . md5($path);

        // Hulu yang menggantung (BacaKomik: 16 dtk lalu 500) tidak dicoba ulang
        // selama 10 menit bila belum pernah ada salinan bagus — klik berikutnya
        // langsung gagal, bukan menunggu 16 detik lagi.
        if (Cache::has($failKey) && !Cache::has($key)) {
            return null;
        }

        return Cache::flexible($key, [$ttl, 86400], function () use ($path, $key, $failKey) {
            $body = $this->fetch($path);
            if ($body === null && !Cache::has($key)) {
                Cache::put($failKey, 1, 600);
            }
            return $body ?? Cache::get($key);
        });
    }

    /** One upstream GET → decoded body, or null when the upstream failed. */
    protected function fetch(string $path): ?array
    {
        try {
            $res = Http::withHeaders([
                'User-Agent' => $this->ua,
                'Accept'     => 'application/json',
            ])
              // anime-api lokal (DrakorKita) merangkai 6-17 dtk saat episode belum
              // ter-cache; tetap di bawah exec_ttl Octane (30 dtk).
              ->timeout($this->timeoutFor($path))
              // Ulang sekali hanya bila koneksi GAGAL DIBUKA. Timeout tidak
              // diulang: hulu yang menggantung (BacaKomik: 16 dtk lalu 500)
              // akan menggantung lagi, dan pengulangan hanya menggandakan waktu
              // tunggu pengguna menjadi ±30 detik.
              ->retry(2, 500, fn ($e) => $e instanceof \Illuminate\Http\Client\ConnectionException
                  && !str_contains($e->getMessage(), 'timed out'), throw: false)
              // Path absolut dipakai sumber yang dilayani API lain (mis. anime-api
              // lokal untuk Oploverz+); selebihnya relatif terhadap basis Sanka.
              ->get(str_starts_with($path, 'http') ? $path : $this->base . $path);

            if (!$res->successful()) {
                return null;
            }

            $body = $this->unwrap($res->json());
            if (!is_array($body) || $body === []) {
                return null;
            }

            // Blokir rate-limit hulu kadang dibalas dengan body JSON biasa;
            // jangan sampai ter-cache sebagai data sah.
            if (($body['status'] ?? null) === 'Too Many Requests') {
                logger()->warning("Sanka API rate-limited [{$path}]: " . ($body['note'] ?? ''));
                return null;
            }

            return $body;
        } catch (\Throwable $e) {
            logger()->warning("Sanka API failed [{$path}]: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Batas tunggu per permintaan. anime-api lokal (DrakorKita) butuh lama saat
     * belum ter-cache. BacaKomik sebaliknya: yang berhasil membalas ±0,1 dtk,
     * yang rusak menggantung 16 dtk lalu 500 — menunggu lebih dari 6 dtk tidak
     * pernah menolong, hanya menunda halaman gagal.
     */
    protected function timeoutFor(string $path): int
    {
        if (str_starts_with($path, 'http://127.0.0.1')) {
            return 25;
        }
        if (str_contains($path, '/comic/bacakomik/')) {
            return 6;
        }
        return 15;
    }

    /**
     * Sejak Sep 2026 hulu membungkus sebagian respons (acak, tidak konsisten
     * per endpoint) menjadi {"_encsankaa": base64url(zlib(json))}.
     * Body polos dikembalikan apa adanya.
     */
    protected function unwrap(mixed $body): mixed
    {
        if (!is_array($body) || !is_string($body['_encsankaa'] ?? null)) {
            return $body;
        }

        $bin = base64_decode(strtr($body['_encsankaa'], '-_', '+/'), true);
        $raw = $bin === false ? false : @zlib_decode($bin);

        return $raw === false ? null : json_decode($raw, true);
    }
}
