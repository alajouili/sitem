<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;
use Closure;

/**
 * Minimal, dependency-free router.
 *
 * Supports:
 *  - GET/POST/PUT/PATCH/DELETE registration
 *  - Dynamic segments: /archives/{id}
 *  - Per-route middleware stacks (Middleware classes must expose handle(Request, Closure): Response)
 *  - Route groups with a shared prefix and/or shared middleware
 *  - Controller callables as [ControllerClass::class, 'method'] or closures
 */
final class Router
{
    private const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /** @var array<string, array<int, array{pattern:string, regex:string, paramNames:array, handler:mixed, middleware:array}>> */
    private array $routes = [];

    private string $groupPrefix = '';
    private array $groupMiddleware = [];

    public function get(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $pattern, $handler, $middleware);
    }

    public function put(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $pattern, $handler, $middleware);
    }

    public function patch(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('PATCH', $pattern, $handler, $middleware);
    }

    public function delete(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $pattern, $handler, $middleware);
    }

    /**
     * Group a set of route registrations under a shared prefix and/or
     * shared middleware stack.
     *
     * Example:
     *   $router->group('/api/archives', function (Router $router) {
     *       $router->get('', [ArchiveController::class, 'index']);
     *       $router->get('/{id}', [ArchiveController::class, 'show']);
     *   }, [AuthMiddleware::class]);
     */
    public function group(string $prefix, Closure $callback, array $middleware = []): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $previousPrefix . $prefix;
        $this->groupMiddleware = [...$previousMiddleware, ...$middleware];

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function addRoute(string $method, string $pattern, callable|array $handler, array $middleware): void
    {
        $fullPattern = $this->normalize($this->groupPrefix . $pattern);
        $allMiddleware = [...$this->groupMiddleware, ...$middleware];

        [$regex, $paramNames] = $this->compilePattern($fullPattern);

        $this->routes[$method][] = [
            'pattern'    => $fullPattern,
            'regex'      => $regex,
            'paramNames' => $paramNames,
            'handler'    => $handler,
            'middleware' => $allMiddleware,
        ];
    }

    private function normalize(string $pattern): string
    {
        $pattern = '/' . ltrim($pattern, '/');
        $pattern = $pattern !== '/' ? rtrim($pattern, '/') : $pattern;

        return $pattern === '' ? '/' : $pattern;
    }

    /**
     * Turns "/archives/{id}" into a regex + list of captured param names.
     */
    private function compilePattern(string $pattern): array
    {
        $paramNames = [];

        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            return '([^/]+)';
        }, $pattern);

        return ['#^' . $regex . '$#', $paramNames];
    }

    /**
     * Match the request against registered routes and run the
     * middleware -> handler pipeline, returning the final Response.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $uri = $this->normalize($request->uri());

        // Handle CORS preflight generically even if no OPTIONS route is registered
        if ($method === 'OPTIONS') {
            return Response::noContent();
        }

        if (!in_array($method, self::METHODS, true)) {
            return Response::error('Method not allowed.', 405);
        }

        $allowedMethodsForUri = [];

        foreach (self::METHODS as $candidateMethod) {
            foreach ($this->routes[$candidateMethod] ?? [] as $route) {
                if (preg_match($route['regex'], $uri, $matches)) {
                    if ($candidateMethod !== $method) {
                        $allowedMethodsForUri[] = $candidateMethod;
                        continue;
                    }

                    $params = $this->extractParams($route['paramNames'], $matches);
                    $request->setRouteParams($params);

                    return $this->runPipeline($request, $route['middleware'], $route['handler']);
                }
            }
        }

        if (!empty($allowedMethodsForUri)) {
            return Response::error('Method not allowed.', 405, ['allowed' => array_unique($allowedMethodsForUri)]);
        }

        throw new NotFoundException("Route {$method} {$uri} not found.");
    }

    private function extractParams(array $paramNames, array $matches): array
    {
        $params = [];

        foreach ($paramNames as $index => $name) {
            $params[$name] = $matches[$index + 1] ?? null;
        }

        return $params;
    }

    /**
     * Build and run the middleware chain around the final handler.
     * Each middleware class must implement:
     *   public function handle(Request $request, Closure $next): Response
     *
     * $middlewareClasses entries may be a class-string (instantiated with
     * no constructor args) or an already-configured instance (e.g.
     * RoleMiddleware::only(['admin'])).
     */
    private function runPipeline(Request $request, array $middlewareClasses, callable|array $handler): Response
    {
        $core = function (Request $request) use ($handler): Response {
            return $this->callHandler($handler, $request);
        };

        $pipeline = array_reduce(
            array_reverse($middlewareClasses),
            function (Closure $next, string|object $middleware) {
                return function (Request $request) use ($middleware, $next): Response {
                    $instance = is_object($middleware) ? $middleware : new $middleware();
                    return $instance->handle($request, $next);
                };
            },
            $core
        );

        return $pipeline($request);
    }

    private function callHandler(callable|array $handler, Request $request): Response
    {
        if ($handler instanceof Closure || (is_string($handler) && is_callable($handler))) {
            return $handler($request);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = new $class();

            return $controller->{$method}($request);
        }

        throw new NotFoundException('Route handler is not callable.');
    }
}