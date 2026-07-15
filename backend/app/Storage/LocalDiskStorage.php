<?php

declare(strict_types=1);

namespace App\Storage;

use App\Config\Env;
use App\Exceptions\StorageException;

/**
 * Stores files on local disk under backend/storage/. Paths passed in are
 * always relative to that root (e.g. "uploads/images/foo.png") — never
 * accept an absolute or ../-containing path from user input without
 * sanitizing first (see FileHelper::sanitizeFilename()).
 */
final class LocalDiskStorage implements StorageInterface
{
    private string $root;

    public function __construct(?string $root = null)
    {
        $this->root = rtrim($root ?? dirname(__DIR__, 2) . '/storage', '/');
    }

    public function put(string $path, string $contents): string
    {
        $full = $this->fullPath($path);
        $this->ensureDirectoryExists(dirname($full));

        if (@file_put_contents($full, $contents) === false) {
            throw new StorageException("Unable to write file to storage: {$path}");
        }

        return $this->relative($path);
    }

    public function putFile(string $path, string $sourcePath): string
    {
        if (!is_file($sourcePath)) {
            throw new StorageException("Source file does not exist: {$sourcePath}");
        }

        $full = $this->fullPath($path);
        $this->ensureDirectoryExists(dirname($full));

        $moved = is_uploaded_file($sourcePath)
            ? @move_uploaded_file($sourcePath, $full)
            : @copy($sourcePath, $full);

        if (!$moved) {
            throw new StorageException("Unable to store uploaded file at: {$path}");
        }

        return $this->relative($path);
    }

    public function get(string $path): string
    {
        $full = $this->fullPath($path);

        if (!is_file($full)) {
            throw new StorageException("File not found in storage: {$path}");
        }

        $contents = @file_get_contents($full);

        if ($contents === false) {
            throw new StorageException("Unable to read file from storage: {$path}");
        }

        return $contents;
    }

    public function exists(string $path): bool
    {
        return is_file($this->fullPath($path));
    }

    public function delete(string $path): bool
    {
        $full = $this->fullPath($path);

        if (!is_file($full)) {
            return false;
        }

        // False positive: fullPath() always routes through relative(),
        // which rejects any ".." path segment before this point.
        return @unlink($full); // nosemgrep: php.lang.security.unlink-use.unlink-use
    }

    public function fullPath(string $path): string
    {
        return $this->root . '/' . ltrim($this->relative($path), '/');
    }

    public function url(string $path): string
    {
        $base = rtrim((string) Env::get('APP_URL', ''), '/');

        return $base . '/api/files/' . ltrim($this->relative($path), '/');
    }

    public function size(string $path): int
    {
        $full = $this->fullPath($path);

        return is_file($full) ? (int) filesize($full) : 0;
    }

    /**
     * Strips any leading slashes and rejects path traversal so callers
     * can't be tricked into writing/reading outside the storage root.
     */
    private function relative(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        $segments = array_filter(explode('/', $path), fn ($s) => $s !== '' && $s !== '.');

        foreach ($segments as $segment) {
            if ($segment === '..') {
                throw new StorageException('Path traversal is not allowed in storage paths.');
            }
        }

        return implode('/', $segments);
    }

    private function ensureDirectoryExists(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new StorageException("Unable to create storage directory: {$dir}");
        }
    }
}