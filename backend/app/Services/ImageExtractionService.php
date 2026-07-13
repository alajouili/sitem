<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ExcelHelper;
use App\Helpers\FileHelper;
use App\Helpers\ImageHelper;
use App\Models\Image;
use App\Repositories\ImageRepository;
use App\Storage\StorageInterface;

/**
 * Extracts images embedded in an .xlsx workbook (xl/media/*) and stores
 * them on disk + as Image records. When a $rowToArchiveId map is
 * supplied (built by ExcelImportService from the row each Archive was
 * created from), extracted images are linked to the matching archive.
 * Images whose anchor row has no corresponding archive (or workbooks
 * with no anchor information at all) are still stored, but with a null
 * archive_id, so nothing is silently dropped.
 */
final class ImageExtractionService
{
    private const STORAGE_PREFIX = 'uploads/images';

    public function __construct(
        private readonly StorageInterface $storage,
        private readonly ImageRepository $images = new ImageRepository()
    ) {
    }

    /**
     * @param array<int,int> $rowToArchiveId sheet row number => archive id
     * @return Image[]
     */
    public function extractAndStore(string $xlsxPath, array $rowToArchiveId = [], int|string $sheet = 0): array
    {
        $anchoredMedia = ExcelHelper::extractMediaWithAnchors($xlsxPath, $sheet);
        $created = [];

        foreach ($anchoredMedia as $media) {
            if (!ImageHelper::isSupportedMime($media['mime'])) {
                continue;
            }

            $archiveId = $media['row'] !== null ? ($rowToArchiveId[$media['row']] ?? null) : null;

            $storedName = FileHelper::uniqueFilename($media['name']);
            $relativePath = self::STORAGE_PREFIX . '/' . $storedName;
            $this->storage->put($relativePath, $media['contents']);

            $dimensions = ImageHelper::dimensionsFromBinary($media['contents']);

            $created[] = $this->images->create(
                archiveId: $archiveId,
                path: $relativePath,
                originalName: $media['name'],
                mimeType: $media['mime'],
                size: strlen($media['contents']),
                width: $dimensions[0] ?? null,
                height: $dimensions[1] ?? null,
            );
        }

        return $created;
    }

    /**
     * Stores a single directly-uploaded image (not from an Excel import),
     * e.g. a manual attachment via ImageController.
     */
    public function storeUploadedImage(array $file, ?int $archiveId): Image
    {
        $contents = file_get_contents($file['tmp_name']);
        $mime = ImageHelper::mimeFromBinary($contents);

        $storedName = FileHelper::uniqueFilename($file['name']);
        $relativePath = self::STORAGE_PREFIX . '/' . $storedName;
        $this->storage->put($relativePath, $contents);

        $dimensions = ImageHelper::dimensionsFromBinary($contents);

        return $this->images->create(
            archiveId: $archiveId,
            path: $relativePath,
            originalName: (string) $file['name'],
            mimeType: $mime,
            size: strlen($contents),
            width: $dimensions[0] ?? null,
            height: $dimensions[1] ?? null,
        );
    }
}