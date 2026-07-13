<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AuthenticationException;
use App\Services\AuthService;
use Closure;

/**
 * Requires a valid `Authorization: Bearer <token>` header. On success,
 * attaches the authenticated App\Models\User to the request under the
 * "user" attribute (read it via $request->getAttribute('user')).
 */
final class AuthMiddleware
{
    private AuthService $auth;

    public function __construct(?AuthService $auth = null)
    {
        $this->auth = $auth ?? new AuthService();
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new AuthenticationException('Missing authentication token.');
        }

        $user = $this->auth->userFromToken($token);
        $request->setAttribute('user', $user);

        return $next($request);
    }
}