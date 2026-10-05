<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Paksa HTTPS di Production/VPS agar tidak terjadi Mixed Content
        // Akar URL TIDAK dipaku ke APP_URL: aplikasi ini dilayani di subfolder
        // (beoulve-dev.biz.id/manco-sync) dan di domain sendiri sekaligus, dan
        // akarnya diambil dari permintaan (lihat trustProxies di bootstrap/app.php).
        // Tanpa permintaan (antrean, jadwal, artisan) Laravel memakai APP_URL.
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Implicitly grant "Superadmin" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole(['Superadmin', 'superadmin']) ? true : null;
        });

        // Share settings globally to all views
        View::composer('*', function ($view) {
            try {
                if (Schema::hasTable('settings')) {
                    $appSettings = \App\Models\Setting::allCached();
                    $view->with('appSettings', $appSettings);
                }
            } catch (\Exception $e) {
                $view->with('appSettings', []);
            }
        });
    }
}
