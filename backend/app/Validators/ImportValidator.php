<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;
use App\Helpers\FileHelper;

final class ImportValidator
{
    private const ALLOWED_EXTENSIONS = ['xlsx'];
    private const MAX_SIZE_BYTES = 20 * 1024 * 1024; // 20MB

    public static function rulesForStore(): array
    {
        return [
            'label' => 'nullable|string|max:255',
        ];
    }

    /**
     * Validates the uploaded file array from Request::file('file').
     * Throws ValidationException on failure rather than returning a bool,
     * since a missing/invalid file is not something the caller should
     * silently proceed past.
     */
    public static function validateUploadedFile(?array $file): void
    {
        $errors = [];

        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors['file'][] = 'An .xlsx file is required.';
            throw new ValidationException($errors);
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors['file'][] = 'The file failed to upload.';
        }

        if (!FileHelper::isAllowedExtension((string) ($file['name'] ?? ''), self::ALLOWED_EXTENSIONS)) {
            $errors['file'][] = 'Only .xlsx files are supported.';
        }

        if (($file['size'] ?? 0) > self::MAX_SIZE_BYTES) {
            $errors['file'][] = 'The file must not be larger than 20MB.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}