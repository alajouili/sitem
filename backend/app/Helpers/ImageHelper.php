<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Image validation + lightweight thumbnailing (via GD) for images
 * extracted from Excel imports or uploaded directly.
 */
final class ImageHelper
{
    private const ALLOWED_MIME_TO_EXTENSION = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        'image/bmp'  => 'bmp',
    ];

    public static function isSupportedMime(string $mime): bool
    {
        return array_key_exists($mime, self::ALLOWED_MIME_TO_EXTENSION);
    }

    public static function extensionForMime(string $mime): ?string
    {
        return self::ALLOWED_MIME_TO_EXTENSION[$mime] ?? null;
    }

    public static function mimeFromBinary(string $binary): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $binary) ?: 'application/octet-stream';
        finfo_close($finfo);

        return $mime;
    }

    /**
     * Returns [width, height] for raw image bytes, or null if it cannot
     * be parsed as an image.
     */
    public static function dimensionsFromBinary(string $binary): ?array
    {
        $info = @getimagesizefromstring($binary);

        if ($info === false) {
            return null;
        }

        return [$info[0], $info[1]];
    }

    /**
     * Generates a resized (max $maxDimension on the longest side) copy of
     * the image, preserving aspect ratio and format. Returns raw bytes,
     * or null if GD cannot decode the source.
     */
    public static function thumbnail(string $binary, int $maxDimension = 300): ?string
    {
        $source = @imagecreatefromstring($binary);

        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        $ratio = min($maxDimension / $width, $maxDimension / $height, 1.0);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $thumb = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG/GIF
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefill($thumb, 0, 0, $transparent);

        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagepng($thumb, null, 6);
        $bytes = ob_get_clean();

        imagedestroy($source);
        imagedestroy($thumb);

        return $bytes === false ? null : $bytes;
    }
}