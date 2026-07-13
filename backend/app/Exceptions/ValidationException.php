<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when input fails validation. Carries the field => [messages]
 * error map in $context so it can be surfaced directly in the JSON
 * response body. Mapped to HTTP 422 by ExceptionHandler.
 */
class ValidationException extends AppException
{
    protected int $statusCode = 422;

    public function __construct(array $errors, string $message = 'The given data was invalid.')
    {
        parent::__construct($message, 0, null, $errors);
    }

    public function errors(): array
    {
        return $this->context ?? [];
    }
}