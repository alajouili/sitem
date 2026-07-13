<?php

declare(strict_types=1);

namespace App\Storage;

/**
 * Storage backend contract. LocalDiskStorage is the only implementation
 * today, but controllers/services depend on this interface so a future
 * S3/cloud backend can be swapped in without touching calling code.
 */
interface StorageInterface
{
    /**
     * Write raw bytes to $path (relative to the storage root). Returns
     * the relative path actually stored at.
     */
    public function put(string $path, string $contents): string;

    /**
     * Copy an existing filesystem file (e.g. from $_FILES tmp_name) into
     * storage at $path. Returns the relative path stored at.
     */
    public function putFile(string $path, string $sourcePath): string;

    public function get(string $path): string;

    public function exists(string $path): bool;

    public function delete(string $path): bool;

    /**
     * Absolute filesystem path for a relative storage path.
     */
    public function fullPath(string $path): string;

    /**
     * Public-facing URL/reference for a stored file (route-based, since
     * this is disk storage rather than a public webroot).
     */
    public function url(string $path): string;

    public function size(string $path): int;
}