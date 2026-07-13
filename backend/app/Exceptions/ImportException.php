<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown by ExcelImportService/ImageExtractionService when a spreadsheet
 * import cannot be completed (bad format, missing columns, corrupt file).
 */
class ImportException extends AppException
{
    protected int $statusCode = 422;

    public function __construct(string $message = 'The import could not be processed.', ?array $context = null)
    {
        parent::__construct($message, 0, null, $context);
    }
}