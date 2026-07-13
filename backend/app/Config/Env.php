<?php

declare(strict_types=1);

namespace App\Config;


final class Env
{
    private static bool $loaded = false;
    private static array $values = [];

    
    public static function load(?string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        $path ??= dirname(__DIR__, 2) . '/.env';

        if (is_file($path) && is_readable($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $line = trim($line);

                
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                if (!str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = self::stripQuotes(trim($value));

                self::$values[$name] = $value;

                
                if (getenv($name) === false) {
                    putenv("{$name}={$value}");
                }
                if (!isset($_ENV[$name])) {
                    $_ENV[$name] = $value;
                }
                if (!isset($_SERVER[$name])) {
                    $_SERVER[$name] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    
    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();

        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return self::castValue($value);
    }

    public static function all(): array
    {
        self::load();

        return self::$values;
    }

    private static function castValue(string $value): mixed
    {
        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }

    private static function stripQuotes(string $value): string
    {
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }
}