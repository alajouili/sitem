<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a requested resource (record, route, file) does not exist.
 * Mapped to HTTP 404 by ExceptionHandler.
 */
class NotFoundException extends AppException
{
    protected int $statusCode = 404;

    public function __construct(string $message = 'Resource not found.', ?array $context = null)
    {
        parent::__construct($message, 0, null, $context);
    }
}