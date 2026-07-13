<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Base class for all application-specific exceptions. Carries an HTTP
 * status code so Core/ExceptionHandler.php can render a proper response
 * without a big switch/match statement of class names.
 */
class AppException extends Exception
{
    protected int $statusCode = 500;

    /** @var array<string,mixed>|null extra structured error detail (e.g. validation errors) */
    protected ?array $context = null;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, ?array $context = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }
}