<?php

declare(strict_types=1);

namespace App\Helpers;

final class StringHelper
{
    public static function slug(string $value, string $separator = '-'): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/u', $separator, $value) ?? '';

        return trim($value, $separator);
    }

    public static function random(int $length = 32): string
    {
        $bytes = random_bytes((int) ceil($length / 2));

        return substr(bin2hex($bytes), 0, $length);
    }

    public static function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function snakeToCamel(string $value): string
    {
        return lcfirst(str_replace('_', '', ucwords($value, '_')));
    }

    public static function camelToSnake(string $value): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }

    public static function limit(string $value, int $length = 100, string $end = '...'): string
    {
        if (mb_strlen($value) <= $length) {
            return $value;
        }

        return mb_substr($value, 0, $length) . $end;
    }

    public static function containsInsensitive(string $haystack, string $needle): bool
    {
        return $needle === '' || mb_stripos($haystack, $needle) !== false;
    }
}