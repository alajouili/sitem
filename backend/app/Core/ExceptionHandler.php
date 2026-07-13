<?php

declare(strict_types=1);

namespace App\Core;

use App\Config\Env;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use ErrorException;
use Throwable;

/**
 * Registers global PHP error/exception/shutdown handlers and turns any
 * uncaught Throwable into a consistent JSON error response, while logging
 * full details to storage/logs.
 */
final class ExceptionHandler
{
    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0'); // never leak raw PHP errors to the client

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Converts classic PHP warnings/notices into exceptions so they flow
     * through the same handling path as everything else.
     */
    public static function handleError(int $severity, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleException(Throwable $e): void
    {
        self::log($e);

        $response = self::render($e);
        $response->send();
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $e = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            self::handleException($e);
        }
    }

    public static function render(Throwable $e): Response
    {
        $debug = (bool) Env::get('APP_DEBUG', false);

        if ($e instanceof ValidationException) {
            return Response::error($e->getMessage(), $e->getStatusCode(), $e->errors());
        }

        if ($e instanceof AppException) {
            $payload = $debug ? $e->getContext() : null;

            return Response::error($e->getMessage(), $e->getStatusCode(), $payload);
        }

        $message = $debug ? $e->getMessage() : 'An unexpected error occurred.';
        $errors = $debug
            ? [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => explode("\n", $e->getTraceAsString()),
            ]
            : null;

        return Response::error($message, 500, $errors);
    }

    private static function log(Throwable $e): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';

        $line = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}