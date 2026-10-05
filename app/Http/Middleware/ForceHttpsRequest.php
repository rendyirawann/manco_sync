<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * TLS diakhiri di reserve-proxy; nginx lokal meneruskan X-Forwarded-Proto: http.
 * Karena proxy kini dipercaya (trustProxies), header itu membuat fullUrl() dan
 * url.intended ber-skema http. Di production semua lalu lintas publik adalah
 * HTTPS, jadi permintaannya ditandai HTTPS sebelum apa pun membaca skemanya.
 */
class ForceHttpsRequest
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('production')) {
            $request->headers->set('X-Forwarded-Proto', 'https');
            $request->server->set('HTTPS', 'on');
        }

        return $next($request);
    }
}
