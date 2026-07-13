<?php

declare(strict_types=1);

namespace App\Helpers;

final class FileHelper
{
    /**
     * Removes path separators and unsafe characters from a user-supplied
     * filename, keeping the original extension.
     */
    public static function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);

        $name = preg_replace('/[^A-Za-z0-9_\-]+/', '-', $name) ?? 'file';
        $name = trim($name, '-') ?: 'file';

        return $extension !== '' ? "{$name}.{$extension}" : $name;
    }

    /**
     * Builds a collision-safe stored filename: {slug}-{uniqid}.{ext}
     */
    public static function uniqueFilename(string $originalFilename): string
    {
        $safe = self::sanitizeFilename($originalFilename);
        $extension = pathinfo($safe, PATHINFO_EXTENSION);
        $name = pathinfo($safe, PATHINFO_FILENAME);
        $token = StringHelper::random(10);

        return $extension !== '' ? "{$name}-{$token}.{$extension}" : "{$name}-{$token}";
    }

    public static function extension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    public static function humanSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        $value = $bytes / (1024 ** $power);

        return round($value, $precision) . ' ' . $units[$power];
    }

    public static function mimeFromPath(string $path): string
    {
        if (!is_file($path)) {
            return 'application/octet-stream';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path) ?: 'application/octet-stream';
        finfo_close($finfo);

        return $mime;
    }

    public static function isAllowedExtension(string $filename, array $allowed): bool
    {
        return in_array(self::extension($filename), array_map('strtolower', $allowed), true);
    }
}