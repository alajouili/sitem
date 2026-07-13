<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\Env;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Adds CORS headers to the response. public/index.php already sets a
 * blanket CORS header for every request; this middleware exists for
 * routes that need finer-grained control (e.g. credentials support or a
 * stricter allow-list) without changing the global bootstrap behavior.
 */
final class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $allowedOrigins = (string) Env::get('CORS_ALLOWED_ORIGINS', '*');
        $origin = (string) $request->header('Origin', '');

        $allowOrigin = $allowedOrigins === '*'
            ? '*'
            : (in_array($origin, array_map('trim', explode(',', $allowedOrigins)), true) ? $origin : 'null');

        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Vary', 'Origin');
    }
}