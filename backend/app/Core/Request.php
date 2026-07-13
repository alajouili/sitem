<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Wraps the incoming HTTP request: method, URI, headers, query string,
 * parsed body (JSON or form-encoded), files, and route parameters.
 */
final class Request
{
    private string $method;
    private string $uri;
    private array $query;
    private array $body;
    private array $headers;
    private array $files;
    private array $server;
    /** @var array<string,string> populated by the Router once a route matches */
    private array $routeParams = [];

    /** @var array<string,mixed> generic bag middleware can use to pass data downstream (e.g. the authenticated user) */
    private array $attributes = [];

    private function __construct(
        string $method,
        string $uri,
        array $query,
        array $body,
        array $headers,
        array $files,
        array $server
    ) {
        $this->method = $method;
        $this->uri = $uri;
        $this->query = $query;
        $this->body = $body;
        $this->headers = $headers;
        $this->files = $files;
        $this->server = $server;
    }

    /**
     * Build a Request instance from PHP superglobals + raw input stream.
     */
    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $uriPath = '/' . ltrim($uriPath, '/');
        $uriPath = $uriPath !== '/' ? rtrim($uriPath, '/') : $uriPath;

        $headers = self::captureHeaders();
        $rawBody = file_get_contents('php://input') ?: '';

        $body = $_POST;

        $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '';
        if (str_contains($contentType, 'application/json') && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $body = $decoded;
            }
        }

        return new self(
            $method,
            $uriPath,
            $_GET,
            $body,
            $headers,
            $_FILES,
            $_SERVER
        );
    }

    private static function captureHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            return $headers === false ? [] : $headers;
        }

        // Fallback for SAPIs without getallheaders() (e.g. php-fpm+nginx edge cases)
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $all = array_merge($this->query, $this->body, $this->routeParams);

        if ($key === null) {
            return $all;
        }

        return $all[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body, $this->routeParams);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function header(string $key, mixed $default = null): mixed
    {
        foreach ($this->headers as $name => $value) {
            if (strcasecmp($name, $key) === 0) {
                return $value;
            }
        }

        return $default;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function bearerToken(): ?string
    {
        $header = (string) $this->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function ip(): string
    {
        return (string) ($this->server['HTTP_X_FORWARDED_FOR'] ?? $this->server['REMOTE_ADDR'] ?? '');
    }

    /**
     * Set the dynamic route parameters matched by the Router (e.g. {id}).
     * Internal use — called by Router::dispatch().
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function routeParam(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function routeParams(): array
    {
        return $this->routeParams;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}