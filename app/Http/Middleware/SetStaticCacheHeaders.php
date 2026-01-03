<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetStaticCacheHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $path = ltrim($request->path(), '/');

        // If request looks like a static asset, add long-term caching headers.
        if (preg_match('/\.(?:js|css|png|jpg|jpeg|svg|webp|ico|map)$/i', $path) || str_starts_with($path, 'build/') || str_starts_with($path, 'assets/')) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        }

        return $response;
    }
}
