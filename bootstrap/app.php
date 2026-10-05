<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Header keamanan (HSTS, CSP, X-Frame-Options, dll) untuk seluruh respons.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Hanya nginx lokal yang bicara ke Octane. Mempercayainya membuat host,
        // skema dan X-Forwarded-Prefix ikut terbaca, sehingga URL yang dihasilkan
        // mengikuti alamat yang dipakai pengunjung: subfolder /manco-sync maupun
        // domain sendiri (manco.hustlesync.my.id) sama-sama benar.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
        $middleware->prepend(\App\Http\Middleware\ForceHttpsRequest::class);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'forbid-banned-user' => \Cog\Laravel\Ban\Http\Middleware\ForbidBannedUser::class,
            'superadmin' => \App\Http\Middleware\EnsureSuperadmin::class,
        ]);

        // 🔥 TAMBAHKAN BARIS INI (Agar logoutOtherDevices berfungsi)
        $middleware->web(append: [
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        // 🔥 TAMBAHKAN KODE INI UNTUK MENGECUALIKAN WEBHOOK MIDTRANS DARI CSRF
        $middleware->validateCsrfTokens(except: [
            'api/midtrans-webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
