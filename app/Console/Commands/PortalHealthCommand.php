<?php

namespace App\Console\Commands;

use App\Services\Portal\SourceHealth;
use Illuminate\Console\Command;

/**
 * Periksa & perbaiki semua sumber portal. Dijalankan oleh
 * /home/user/portal-watchdog.sh (cron): tiap 30 menit cepat, tiap 2 jam penuh,
 * dan segera bila superadmin menekan tombol "Cek & Perbaiki" (--if-requested).
 */
class PortalHealthCommand extends Command
{
    protected $signature = 'portal:health
        {--full : Uji semua tab tiap sumber (maks. 4), bukan hanya tab pertama}
        {--category= : Batasi ke satu kategori}
        {--if-requested : Hanya jalan bila ada permintaan dari tombol superadmin}
        {--fix=* : Perbaiki sumber tertentu saja, mis. --fix=anime:samehadaku}';

    protected $description = 'Periksa semua sumber portal dan perbaiki otomatis (cache, sumber pulih, sumber bawaan)';

    public function handle(SourceHealth $health): int
    {
        $full = (bool) $this->option('full');
        if ($fix = (array) $this->option('fix')) {
            $report = $health->run(true, null, fn ($l) => $this->line($l), $fix);
            foreach ($report['actions'] ?? [] as $a) {
                $this->info('• ' . $a);
            }
            return self::SUCCESS;
        }
        if ($this->option('if-requested')) {
            $file = SourceHealth::requestFile();
            if (!is_file($file)) {
                return self::SUCCESS;
            }
            // Pemeriksaan lain masih berjalan: biarkan berkas permintaan, ambil menit berikutnya.
            $cur = \Illuminate\Support\Facades\Cache::get(SourceHealth::REPORT, []);
            if (!empty($cur['running']) && time() - (int) ($cur['started'] ?? 0) < 1200) {
                return self::SUCCESS;
            }
            $req = json_decode((string) @file_get_contents($file), true) ?: [];
            @unlink($file);
            $targets = array_values(array_unique(array_filter((array) ($req['targets'] ?? []), 'is_string')));
            // Tombol "Perbaiki" (per sumber) didahulukan; "Cek & Perbaiki Semua" sesudahnya.
            if ($targets) {
                $report = $health->run(true, null, fn ($l) => $this->line($l), $targets);
                foreach ($report['actions'] ?? [] as $a) {
                    $this->info('• ' . $a);
                }
                if (empty($req['full'])) {
                    return self::SUCCESS;
                }
            }
            $full = $full || !empty($req['full']);
        }

        $report = $health->run($full, $this->option('category') ?: null, fn ($l) => $this->line($l));
        foreach ($report['actions'] ?? [] as $a) {
            $this->info('• ' . $a);
        }
        return self::SUCCESS;
    }
}
