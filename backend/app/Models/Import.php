<?php

declare(strict_types=1);

namespace App\Models;

final class Import
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public ?int $id;
    public ?int $userId;
    public string $filename;
    public string $status;
    public int $totalRows;
    public int $processedRows;
    public array $errorLog;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(
        ?int $id,
        ?int $userId,
        string $filename,
        string $status = self::STATUS_PENDING,
        int $totalRows = 0,
        int $processedRows = 0,
        array $errorLog = [],
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->filename = $filename;
        $this->status = $status;
        $this->totalRows = $totalRows;
        $this->processedRows = $processedRows;
        $this->errorLog = $errorLog;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromRow(array $row): self
    {
        $errorLog = $row['error_log'] ?? '[]';
        if (is_string($errorLog)) {
            $errorLog = json_decode($errorLog, true) ?: [];
        }

        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            userId: isset($row['user_id']) ? (int) $row['user_id'] : null,
            filename: (string) $row['filename'],
            status: (string) ($row['status'] ?? self::STATUS_PENDING),
            totalRows: (int) ($row['total_rows'] ?? 0),
            processedRows: (int) ($row['processed_rows'] ?? 0),
            errorLog: $errorLog,
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'user_id'         => $this->userId,
            'filename'        => $this->filename,
            'status'          => $this->status,
            'total_rows'      => $this->totalRows,
            'processed_rows'  => $this->processedRows,
            'error_log'       => $this->errorLog,
            'created_at'      => $this->createdAt,
            'updated_at'      => $this->updatedAt,
        ];
    }
}