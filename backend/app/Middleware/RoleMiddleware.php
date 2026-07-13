<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AuthenticationException;
use App\Models\User;
use Closure;

/**
 * Restricts a route to one or more roles. Must be registered AFTER
 * AuthMiddleware in the route's middleware stack, since it reads the
 * "user" attribute AuthMiddleware attaches.
 *
 * Usage: $router->get('/admin/x', $handler, [AuthMiddleware::class, RoleMiddleware::only(['admin'])]);
 */
final class RoleMiddleware
{
    private array $allowedRoles;

    private function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public static function only(array $roles): self
    {
        return new self($roles);
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->getAttribute('user');

        if ($user === null) {
            // Misconfigured route (RoleMiddleware without AuthMiddleware first)
            throw new AuthenticationException('Unauthenticated.');
        }

        if (!in_array($user->role, $this->allowedRoles, true)) {
            return Response::error('You do not have permission to perform this action.', 403);
        }

        return $next($request);
    }
}