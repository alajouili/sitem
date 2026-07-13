<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Represents an outgoing HTTP response. Build it with the static helpers
 * and return it from a controller; the Router will call send() on it.
 */
final class Response
{
    private int $status;
    private array $headers;
    private string $body;

    private function __construct(int $status, array $headers, string $body)
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';

        return new self(
            $status,
            $headers,
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}'
        );
    }

    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): self
    {
        return self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null): self
    {
        return self::json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    public static function noContent(): self
    {
        return new self(204, [], '');
    }

    public static function text(string $content, int $status = 200): self
    {
        return new self($status, ['Content-Type' => 'text/plain; charset=utf-8'], $content);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * Emit headers + body to the client. Does not exit — caller controls
     * the process lifecycle (index.php exits after dispatch).
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->body;
    }
}