<?php

namespace App\Services\Portal;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Live TV via iptv-org — an open, public dataset of free-to-air / publicly
 * available channels as .m3u8 streams (the legal equivalent of a friend's IPTV
 * playlist). Includes a big Sports category (free-to-air sports channels that
 * broadcast football, etc.). We only host links, same as iptv-org.
 */
class LiveTvClient
{
    protected string $base = 'https://iptv-org.github.io/iptv';

    /** UI label => iptv-org playlist path. */
    public function categories(): array
    {
        return [
            'Sport'     => 'categories/sports',
            'Indonesia' => 'countries/id',
            'Berita'    => 'categories/news',
            'Kartun'    => 'categories/animation',
            'Movie'     => 'categories/movies',
            'Umum'      => 'categories/entertainment',
        ];
    }

    /** @return array<int,array{name:string,logo:string,group:string,url:string}> */
    public function channels(string $catKey): array
    {
        $map  = $this->categories();
        $path = $map[$catKey] ?? reset($map);
        $ck   = 'iptv:' . md5($path);
        $cached = Cache::get($ck);
        if ($cached !== null) {
            return $cached;
        }
        try {
            $r = Http::timeout(20)->get("{$this->base}/{$path}.m3u");
            if (!$r->successful()) {
                return [];
            }
            $channels = $this->parse($r->body());
            Cache::put($ck, $channels, 3600); // channels change slowly; refresh hourly
            return $channels;
        } catch (\Throwable $e) {
            logger()->warning('iptv-org fetch failed [' . $path . ']: ' . $e->getMessage());
            return [];
        }
    }

    /** Parse an M3U playlist into playable (.m3u8) channels. */
    protected function parse(string $m3u): array
    {
        $out = [];
        $cur = null;
        foreach (preg_split('/\r?\n/', $m3u) as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#EXTINF')) {
                $comma = strrpos($line, ',');
                $name  = $comma !== false ? trim(substr($line, $comma + 1)) : 'Channel';
                preg_match('/tvg-logo="([^"]*)"/', $line, $lg);
                preg_match('/group-title="([^"]*)"/', $line, $gt);
                $cur = ['name' => $name, 'logo' => $lg[1] ?? '', 'group' => $gt[1] ?? ''];
            } elseif ($line !== '' && $line[0] !== '#' && $cur) {
                if (str_contains($line, '.m3u8')) { // only browser-playable HLS
                    $cur['url'] = $line;
                    $out[] = $cur;
                }
                $cur = null;
            }
        }
        return $out;
    }
}
