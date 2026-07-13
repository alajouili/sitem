<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a request lacks valid authentication (missing/invalid/expired
 * token or bad credentials). Mapped to HTTP 401 by ExceptionHandler.
 */
class AuthenticationException extends AppException
{
    protected int $statusCode = 401;

    public function __construct(string $message = 'Unauthenticated.', ?array $context = null)
    {
        parent::__construct($message, 0, null, $context);
    }
}