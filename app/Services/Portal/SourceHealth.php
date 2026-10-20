<?php

namespace App\Services\Portal;

use Illuminate\Support\Facades\Cache;

/**
 * Pemeriksa kesehatan semua sumber portal.
 *
 * Tiap sumber diuji LANGSUNG ke hulu (tanpa cache): daftar pertama (atau semua
 * daftar pada pemeriksaan penuh) lalu detail judul pertamanya. Perbaikan yang
 * dilakukan otomatis — tidak ada sumber yang dihapus atau diubah di config:
 *
 *  - hasil yang berhasil langsung menyegarkan cache dan menghapus tanda gagal
 *    10 menit, jadi sumber yang baru pulih segera tampil lagi;
 *  - sumber 'disabled' yang dua kali berturut-turut sehat ditampilkan lagi
 *    (SourceClient::withoutDisabled), dan disembunyikan lagi bila kambuh;
 *  - sumber bawaan kategori dan tab bawaan sebuah sumber dilewati selama
 *    tercatat mati (SourceClient::defaultSource, firstHealthyList).
 *
 * Layanan lokal (anime-api :8103, tmdb-embed-api :8787) dihidupkan ulang oleh
 * /home/user/portal-watchdog.sh (cron user), bukan dari sini: proses node itu
 * milik user, sedangkan pemeriksa ini bisa berjalan sebagai www-data.
 *
 * Ringan: satu permintaan per detik ke Sanka (rate-limit IP), terjadwal tiap
 * 30 menit, dan hanya satu pemeriksaan berjalan pada satu waktu.
 */
class SourceHealth
{
    public const STATE   = 'portal-health:state';
    public const REPORT  = 'portal-health:report';
    public const REVIVED = 'portal-health:revived';
    public const DOWN    = 'portal-health:down';
    public const BADTABS = 'portal-health:badtabs';
    public const FALLBACK = 'portal-health:fallback';

    /** Berkas permintaan dari tombol superadmin; diambil watchdog cron tiap menit. */
    public static function requestFile(): string
    {
        return storage_path('app/portal-health.request');
    }

    public function __construct(protected SourceClient $client) {}

    /** @return array<string,bool> "cat:src" => true untuk sumber disabled yang pulih */
    public static function revived(): array
    {
        return (array) Cache::get(self::REVIVED, []);
    }

    /** @return array<string,bool> "cat:src" => true untuk sumber aktif yang sedang mati */
    public static function down(): array
    {
        return (array) Cache::get(self::DOWN, []);
    }

    /** @return array<string,bool> "cat:src:tab" => true untuk tab yang kosong di hulu */
    public static function badTabs(): array
    {
        return (array) Cache::get(self::BADTABS, []);
    }

    /** @return array<string,string> "cat:src" => sumber pengganti yang sedang dipakai */
    public static function fallbacks(): array
    {
        return (array) Cache::get(self::FALLBACK, []);
    }

    /** Sumber pengganti aktif untuk cat/src, atau null. */
    public static function fallbackFor(string $cat, string $src): ?string
    {
        return self::fallbacks()["$cat:$src"] ?? null;
    }

    /** Tab pertama yang tidak tercatat kosong (urutan tab tetap; tidak ada yang disembunyikan). */
    public static function firstHealthyList(string $cat, string $src, array $lists): string
    {
        $bad = self::badTabs();
        foreach (array_keys($lists) as $name) {
            if (!isset($bad["$cat:$src:$name"])) {
                return (string) $name;
            }
        }
        return (string) array_key_first($lists);
    }

    /** Hapus semua tanda gagal 10 menit (sanka-fail:*) → halaman langsung mencoba hulu lagi. */
    public static function clearAllFailures(): int
    {
        try {
            $redis = Cache::getStore()->connection();
            $pre = (string) config('database.redis.options.prefix', '');
            $n = 0;
            foreach ($redis->keys('*sanka-fail:*') ?: [] as $k) {
                $n += (int) $redis->del(str_starts_with($k, $pre) ? substr($k, strlen($pre)) : $k);
            }
            return $n;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Status layanan lokal yang dipakai sumber. */
    public static function services(): array
    {
        $out = [];
        foreach (['anime-api' => 8103, 'tmdb-embed-api' => 8787] as $name => $port) {
            $fp = @fsockopen('127.0.0.1', $port, $no, $err, 1.5);
            $out[$name] = ['port' => $port, 'up' => (bool) $fp];
            if ($fp) {
                fclose($fp);
            }
        }
        return $out;
    }

    /**
     * Jalankan pemeriksaan. $full: uji semua tab (maks. 4 per sumber); tanpa itu
     * hanya tab pertama. $only: batasi ke satu kategori.
     */
    public function run(bool $full = false, ?string $only = null, ?callable $out = null, array $targets = []): array
    {
        $lock = Cache::lock('portal-health:run', 1200);
        if (!$lock->get()) {
            $out && $out('Pemeriksaan lain sedang berjalan — dilewati.');
            return Cache::get(self::REPORT, []);
        }

        try {
            return $this->doRun($full, $only, $out, $targets);
        } finally {
            $lock->release();
        }
    }

    protected function doRun(bool $full, ?string $only, ?callable $out, array $targets = []): array
    {
        // Mode "Perbaiki" (tombol per sumber): hanya sumber yang diminta, semua tabnya,
        // diuji ulang bila gagal, dan semua tanda gagal 10 menit dihapus lebih dulu.
        $repair = $targets !== [];
        if ($repair) {
            $full = true;
            $n = self::clearAllFailures();
            $out && $out("Tanda gagal dihapus: $n");
        }
        $state   = (array) Cache::get(self::STATE, []);
        $revived = self::revived();
        $down    = self::down();
        $badTabs = self::badTabs();
        $actions = [];

        $list = [];
        foreach (config('portal_sources', []) as $cat => $c) {
            if ($only && $cat !== $only) {
                continue;
            }
            foreach ($c['sources'] ?? [] as $src => $s) {
                if (!$repair || in_array("$cat:$src", $targets, true)) {
                    $list[] = [$cat, $src, $s];
                }
            }
        }

        $report = [
            'running'  => true,
            'full'     => $full,
            'started'  => time(),
            'finished' => null,
            'progress' => '0/' . count($list),
            'repair'   => $repair ? array_values($targets) : [],
            'services' => self::services(),
            'actions'  => [],
        ];
        Cache::forever(self::REPORT, $report);

        foreach ($report['services'] as $name => $svc) {
            if (!$svc['up']) {
                $actions[] = "Layanan $name (:{$svc['port']}) mati — watchdog cron akan menghidupkannya dalam ±1 menit.";
            }
        }

        foreach ($list as $i => [$cat, $src, $s]) {
            $key   = "$cat:$src";
            $prev  = $state[$key] ?? [];
            $t0    = microtime(true);
            $lists = $s['lists'] ?? [];
            $local = isset($s['base']) && str_contains((string) $s['base'], '127.0.0.1');

            $attempts = $repair ? 3 : 1;
            for ($try = 1; $try <= $attempts; $try++) {
            if ($try > 1) {
                $out && $out("  ulang ke-$try untuk {$key}…");
                sleep(4); // hulu yang tersendat sering pulih dalam hitungan detik
            }
            $tabs = $full ? array_slice($lists, 0, 4, true) : array_slice($lists, 0, 1, true);
            $listRes = [];
            $firstId = null;
            foreach ($tabs as $name => $tpl) {
                $items = $this->client->probeList($cat, $src, str_replace('{p}', '1', (string) $tpl));
                $n = $items === null ? -1 : count($items);
                $listRes[$name] = $n;
                $firstId = $firstId ?? ($items[0]['id'] ?? null);
                $tabKey = "$key:$name";
                if ($n > 0) {
                    unset($badTabs[$tabKey]);
                } else {
                    $badTabs[$tabKey] = true;
                }
                $local || usleep(1000000);
            }
            // Tab pertama gagal tapi sumbernya punya tab lain: coba satu tab lagi
            // supaya "tab kosong" tidak dikira "sumber mati".
            if (!$full && $firstId === null && count($lists) > 1) {
                $name = array_keys($lists)[1];
                $items = $this->client->probeList($cat, $src, str_replace('{p}', '1', (string) $lists[$name]));
                $listRes[$name] = $items === null ? -1 : count($items);
                $firstId = $items[0]['id'] ?? null;
                if ($firstId) {
                    unset($badTabs["$key:$name"]);
                }
                $local || usleep(1000000);
            }

            $detailOk = null;
            if ($firstId !== null && !empty($s['detail'])) {
                $d = $this->client->probeDetail($cat, $src, (string) $firstId);
                $detailOk = $d !== null;
                $local || usleep(1000000);
            }

            if ($firstId !== null && $detailOk !== false) {
                break;
            }
            }

            $listOk = $firstId !== null;
            $status = !$listOk ? 'down' : ($detailOk === false ? 'partial' : 'ok');
            $cfgDisabled = !empty($s['disabled']);

            $entry = [
                'cat'      => $cat,
                'src'      => $src,
                'label'    => $s['label'] ?? $src,
                'status'   => $status,
                'lists'    => $listRes,
                'detail'   => $detailOk,
                'ms'       => (int) round((microtime(true) - $t0) * 1000),
                'checked'  => time(),
                'last_ok'  => $status === 'ok' ? time() : ($prev['last_ok'] ?? null),
                'oks'      => $status === 'ok' ? (($prev['oks'] ?? 0) + 1) : 0,
                'fails'    => $status === 'down' ? (($prev['fails'] ?? 0) + 1) : 0,
                'disabled' => $cfgDisabled,
                'local'    => $local,
            ];

            // Perbaikan otomatis (tanpa menghapus apa pun)
            if ($cfgDisabled) {
                if ($entry['oks'] >= 2 && !isset($revived[$key])) {
                    $revived[$key] = true;
                    $actions[] = "{$entry['label']} ($cat) pulih → ditampilkan lagi.";
                } elseif ($status !== 'ok' && isset($revived[$key])) {
                    unset($revived[$key]);
                    $actions[] = "{$entry['label']} ($cat) kambuh → disembunyikan lagi (config tetap utuh).";
                }
            } else {
                if ($status === 'down' && $entry['fails'] >= 2 && !isset($down[$key])) {
                    $down[$key] = true;
                    $actions[] = "{$entry['label']} ($cat) mati → bukan lagi sumber bawaan (tetap bisa dipilih).";
                } elseif ($status !== 'down' && isset($down[$key])) {
                    unset($down[$key]);
                    $actions[] = "{$entry['label']} ($cat) pulih → kembali normal.";
                }
            }
            if ($status !== 'down' && ($prev['status'] ?? null) === 'down') {
                $actions[] = "{$entry['label']} ($cat): cache disegarkan, tanda gagal dihapus.";
            }
            if ($repair) {
                $actions[] = $status === 'ok'
                    ? "Perbaiki {$entry['label']} ($cat): BERHASIL — sehat, cache disegarkan."
                    : ($status === 'partial'
                        ? "Perbaiki {$entry['label']} ($cat): daftar pulih, tetapi detail masih gagal di hulu."
                        : "Perbaiki {$entry['label']} ($cat): hulu masih tidak mengembalikan data setelah $attempts percobaan"
                          . ($local ? ' (layanan lokal — watchdog menghidupkan ulang bila mati).' : ' — masalah di situs sumber, bukan di server kita.'));
            }

            $state[$key] = $entry;
            $report['progress'] = ($i + 1) . '/' . count($list);
            $report['actions']  = $actions;
            Cache::forever(self::STATE, $state);
            Cache::forever(self::REVIVED, $revived);
            Cache::forever(self::DOWN, $down);
            Cache::forever(self::BADTABS, $badTabs);
            Cache::forever(self::REPORT, $report);

            $out && $out(sprintf('%-9s %-16s %-7s %s detail=%s %dms', $cat, $src, $status,
                json_encode($listRes, JSON_UNESCAPED_UNICODE), var_export($detailOk, true), $entry['ms']));
        }

        // Pengalihan otomatis: sumber yang rusak (mati, atau detail gagal) dan punya
        // 'fallback' sehat → halaman sumber itu dialihkan ke penggantinya. Saat
        // Perbaiki langsung; terjadwal setelah 2x gagal. Pulih → pengalihan dicabut.
        $fallbacks = self::fallbacks();
        foreach (config('portal_sources', []) as $cat => $c) {
            foreach ($c['sources'] ?? [] as $src => $s) {
                $key = "$cat:$src";
                $fb = $s['fallback'] ?? null;
                $e = $state[$key] ?? null;
                if (!$fb || !$e) {
                    continue;
                }
                $fbOk = ($state["$cat:$fb"]['status'] ?? null) === 'ok';
                $broken = $e['status'] !== 'ok' && ($repair && in_array($key, $targets, true) || ($e['fails'] ?? 0) >= 2 || $e['status'] === 'partial');
                $label = $s['label'] ?? $src;
                $fbLabel = $c['sources'][$fb]['label'] ?? $fb;
                if ($broken && $fbOk && !isset($fallbacks[$key])) {
                    $fallbacks[$key] = $fb;
                    $actions[] = "$label ($cat): hulu rusak → dialihkan otomatis ke $fbLabel (sumber asli tetap ada, kembali sendiri bila pulih).";
                } elseif (isset($fallbacks[$key]) && ($e['status'] === 'ok' || !$fbOk)) {
                    unset($fallbacks[$key]);
                    $actions[] = $e['status'] === 'ok' ? "$label ($cat) pulih → pengalihan ke $fbLabel dicabut." : "$fbLabel bermasalah → pengalihan $label dicabut.";
                }
            }
        }
        Cache::forever(self::FALLBACK, $fallbacks);
        if ($repair) {
            foreach ($actions as $i => $a) {
                // Pesan "Perbaiki X: gagal" diganti hasil yang sebenarnya bila X dialihkan.
                foreach ($fallbacks as $k => $fb) {
                    $lbl = $state[$k]['label'] ?? '';
                    if ($lbl !== '' && str_starts_with($a, "Perbaiki $lbl (") && in_array($k, $targets, true)) {
                        $actions[$i] = "Perbaiki $lbl: hulu aslinya rusak, DIPERBAIKI dengan mengalihkan ke " . ($state[explode(':', $k)[0] . ":$fb"]['label'] ?? $fb) . '.';
                    }
                }
            }
        }

        $report['running']  = false;
        $report['finished'] = time();
        $report['actions']  = $actions ?: ['Tidak ada yang perlu diperbaiki.'];
        Cache::forever(self::REPORT, $report);

        return $report;
    }
}
