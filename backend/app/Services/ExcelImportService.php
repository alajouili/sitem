<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ExcelHelper;
use App\Helpers\FileHelper;
use App\Models\Import;
use App\Repositories\ImportRepository;
use App\Storage\StorageInterface;
use App\Validators\ArchiveValidator;

/**
 * Coordinates a full Excel import: stores the raw upload, parses each
 * data row into archive fields, validates + creates an Archive per
 * valid row, extracts embedded images and links them to the archive
 * created from their anchor row, and tracks progress/errors on the
 * Import record throughout.
 */
final class ExcelImportService
{
    private const STORAGE_PREFIX = 'uploads/excel';

    /**
     * Row header labels (case-insensitive) mapped to Archive fields.
     *
     * Note: the spreadsheet's own "Status" column is intentionally NOT
     * mapped here. This app's `status` field is a fixed lifecycle enum
     * (draft/published/archived) — a business-specific status column
     * (e.g. a mission/ticket status) means something different and would
     * fail validation if forced into that enum. It's preserved instead
     * as a regular field inside `metadata`, alongside everything else
     * that doesn't match one of these aliases.
     */
    private const FIELD_ALIASES = [
        'title'       => ['title', 'name', 'code mission', 'dp'],
        'description' => ['description', 'desc', 'notes', 'commentaire'],
        'category'    => ['category', 'type', 'motif'],
        'status'      => ['status', 'etat'],
    ];

    public function __construct(
        private readonly StorageInterface $storage,
        private readonly ImportRepository $imports = new ImportRepository(),
        private readonly ArchiveService $archives = new ArchiveService(),
        private readonly ?ImageExtractionService $imageExtraction = null
    ) {
    }

    /**
     * Stores the uploaded file and creates the Import record in
     * "pending" status. Call run() next (kept as two steps so a
     * controller can return the import id immediately and process
     * synchronously or hand off to a queue later without changing this
     * service's shape).
     */
    public function stageUpload(array $file, ?int $userId): Import
    {
        $storedName = FileHelper::uniqueFilename($file['name']);
        $relativePath = self::STORAGE_PREFIX . '/' . $storedName;
        $this->storage->putFile($relativePath, $file['tmp_name']);

        return $this->imports->create($userId, $relativePath);
    }

    /**
     * Processes a staged import synchronously: parses every data row,
     * validates it, creates an Archive per valid row, extracts +
     * attaches embedded images, and updates the Import's progress and
     * error log as it goes.
     */
    public function run(Import $import, ?int $userId): Import
    {
        $absolutePath = $this->storage->fullPath($import->filename);

        $rows = ExcelHelper::readRows($absolutePath, 0);
        $totalRows = count($rows);

        $this->imports->updateProgress($import->id, Import::STATUS_PROCESSING, $totalRows, 0, []);

        $errors = [];
        $processed = 0;
        $rowToArchiveId = [];

        foreach ($rows as $rowNumber => $row) {
            $fields = $this->mapRowToArchiveFields($row);

            $validated = null;
            try {
                $validated = ValidationService::validate($fields, ArchiveValidator::rulesForCreate());
            } catch (\App\Exceptions\ValidationException $e) {
                $errors[] = ['row' => $rowNumber, 'errors' => $e->errors()];
                $processed++;
                continue;
            }

            $archive = $this->archives->create($validated, $userId);
            $rowToArchiveId[$rowNumber] = $archive->id;
            $processed++;

            $this->imports->updateProgress($import->id, Import::STATUS_PROCESSING, $totalRows, $processed, $errors);
        }

        if ($this->imageExtraction !== null) {
            $this->imageExtraction->extractAndStore($absolutePath, $rowToArchiveId);
        }

        $finalStatus = empty($errors) ? Import::STATUS_COMPLETED : Import::STATUS_COMPLETED;
        // Note: partial row failures don't fail the whole import — the
        // per-row errors are preserved in error_log for the caller to
        // review, while every valid row is still committed.
        if ($processed === 0 || count($errors) === $totalRows) {
            $finalStatus = Import::STATUS_FAILED;
        }

        return $this->imports->updateProgress($import->id, $finalStatus, $totalRows, $processed, $errors);
    }

    private function mapRowToArchiveFields(array $row): array
    {
        $lowerRow = [];
        foreach ($row as $key => $value) {
            $lowerRow[strtolower(trim((string) $key))] = $value;
        }

        $fields = [];
        
        // 1. Map main fields using our aliases (using looser matching for Excel quirks)
        foreach (self::FIELD_ALIASES as $target => $aliases) {
            foreach ($aliases as $alias) {
                foreach ($lowerRow as $actualKey => $actualValue) {
                    if (str_contains($actualKey, $alias) && $actualValue !== null && $actualValue !== '') {
                        $fields[$target] = (string) $actualValue;
                        break 2;
                    }
                }
            }
        }

        // 2. Bulletproof Title Fallback: If it still misses the title, grab the very first column (Code mission)
        if (empty($fields['title']) && !empty($row)) {
            $fields['title'] = (string) reset($row);
        }

        // 3. Smart Status Translation: Converts French Excel to System English
        $rawStatus = strtolower($fields['status'] ?? '');
        if (str_contains($rawStatus, 'termin') || str_contains($rawStatus, 'valid')) {
            $fields['status'] = \App\Models\Archive::STATUS_PUBLISHED;
        } elseif (str_contains($rawStatus, 'archiv')) {
            $fields['status'] = \App\Models\Archive::STATUS_ARCHIVED;
        } else {
            // "En cours", blank, or unrecognized statuses safely become 'draft'
            $fields['status'] = \App\Models\Archive::STATUS_DRAFT;
        }

        // 4. Safely package ALL columns into metadata so React can find everything
        $metadata = [];
        foreach ($lowerRow as $key => $value) {
            // Skip purely empty headers but save all actual data
            if ($key !== '' && $value !== null) {
                $metadata[$key] = $value;
            }
        }
        
        if (!empty($metadata)) {
            $fields['metadata'] = $metadata;
        }

        return $fields;
    }
}