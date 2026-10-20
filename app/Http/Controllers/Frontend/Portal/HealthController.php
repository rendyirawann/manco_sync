<?php

namespace App\Http\Controllers\Frontend\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\SourceHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Panel "Kesehatan Sumber" — khusus Superadmin (middleware superadmin).
 * Tombol "Cek & Perbaiki" tidak menjalankan pemeriksaan di request web (bisa
 * 3–5 menit, melewati batas 30 dtk Octane): ia menulis berkas permintaan yang
 * diambil watchdog cron dalam ±1 menit, lalu halaman memantau kemajuannya.
 */
class HealthController extends Controller
{
    public function index()
    {
        return view('frontend.portal.health', $this->payload());
    }

    public function status()
    {
        return response()->json($this->payload());
    }

    public function run(Request $r)
    {
        // Permintaan digabung (beberapa tombol "Perbaiki" bisa ditekan berurutan sebelum
        // watchdog mengambilnya). target kosong = "Cek & Perbaiki Semua".
        $valid = [];
        foreach (config('portal_sources', []) as $cat => $c) {
            foreach (array_keys($c['sources'] ?? []) as $src) {
                $valid["$cat:$src"] = true;
            }
        }
        $targets = array_values(array_filter((array) $r->input('targets', []), fn ($t) => is_string($t) && isset($valid[$t])));

        $file = SourceHealth::requestFile();
        $req = is_file($file) ? (json_decode((string) @file_get_contents($file), true) ?: []) : [];
        $req['targets'] = array_values(array_unique(array_merge((array) ($req['targets'] ?? []), $targets)));
        $req['full'] = !empty($req['full']) || $targets === [];
        $req['by'] = $r->user()?->id;
        $req['at'] = time();
        @file_put_contents($file, json_encode($req), LOCK_EX);
        @chmod($file, 0664);
        return response()->json(['queued' => is_file($file), 'targets' => $req['targets'], 'full' => $req['full']]);
    }

    protected function payload(): array
    {
        $state = (array) Cache::get(SourceHealth::STATE, []);
        $labels = [];
        foreach (config('portal_sources', []) as $cat => $c) {
            $labels[$cat] = $c['label'] ?? $cat;
        }
        uksort($state, fn ($a, $b) => strcmp($a, $b));
        return [
            'state'    => array_values($state),
            'labels'   => $labels,
            'report'   => Cache::get(SourceHealth::REPORT, []),
            'services' => SourceHealth::services(),
            'fallbacks' => SourceHealth::fallbacks(),
            'pending'  => is_file(SourceHealth::requestFile()),
            'queued'   => is_file(SourceHealth::requestFile())
                ? ((json_decode((string) @file_get_contents(SourceHealth::requestFile()), true) ?: [])['targets'] ?? [])
                : [],
        ];
    }
}
