<?php

declare(strict_types=1);

namespace App\Models;

final class Image
{
    public ?int $id;
    public ?int $archiveId;
    public string $path;
    public string $originalName;
    public string $mimeType;
    public int $size;
    public ?int $width;
    public ?int $height;
    public ?string $createdAt;

    public function __construct(
        ?int $id,
        ?int $archiveId,
        string $path,
        string $originalName,
        string $mimeType,
        int $size,
        ?int $width = null,
        ?int $height = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->archiveId = $archiveId;
        $this->path = $path;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->width = $width;
        $this->height = $height;
        $this->createdAt = $createdAt;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            archiveId: isset($row['archive_id']) ? (int) $row['archive_id'] : null,
            path: (string) $row['path'],
            originalName: (string) $row['original_name'],
            mimeType: (string) $row['mime_type'],
            size: (int) ($row['size'] ?? 0),
            width: isset($row['width']) ? (int) $row['width'] : null,
            height: isset($row['height']) ? (int) $row['height'] : null,
            createdAt: $row['created_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'archive_id'    => $this->archiveId,
            'path'          => $this->path,
            'original_name' => $this->originalName,
            'mime_type'     => $this->mimeType,
            'size'          => $this->size,
            'width'         => $this->width,
            'height'        => $this->height,
            'created_at'    => $this->createdAt,
        ];
    }
}