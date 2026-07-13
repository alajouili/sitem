<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/**
 * Thrown for low-level storage failures: database connection errors,
 * disk write failures, unreachable storage backends, etc.
 */
class StorageException extends AppException
{
    protected int $statusCode = 500;

    public function __construct(string $message = 'A storage error occurred.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}