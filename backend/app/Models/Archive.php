<?php

declare(strict_types=1);

namespace App\Models;

// Added \JsonSerializable interface here
final class Archive implements \JsonSerializable
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_ARCHIVED];

    public ?int $id;
    public string $title;
    public ?string $description;
    public ?string $category;
    public ?string $filePath;
    public array $metadata;
    public string $status;
    public ?int $createdBy;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(
        ?int $id,
        string $title,
        ?string $description,
        ?string $category,
        ?string $filePath,
        array $metadata = [],
        string $status = self::STATUS_DRAFT,
        ?int $createdBy = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->category = $category;
        $this->filePath = $filePath;
        $this->metadata = $metadata;
        $this->status = $status;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromRow(array $row): self
    {
        $metadata = $row['metadata'] ?? '[]';
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            title: (string) $row['title'],
            description: $row['description'] ?? null,
            category: $row['category'] ?? null,
            filePath: $row['file_path'] ?? null,
            metadata: $metadata,
            status: (string) ($row['status'] ?? self::STATUS_DRAFT),
            createdBy: isset($row['created_by']) ? (int) $row['created_by'] : null,
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        $normalizedMetadata = [];
        
        if (is_array($this->metadata)) {
            foreach ($this->metadata as $key => $value) {
                $lowerKey = strtolower(trim((string)$key));
                
                // Keep the raw normalized key
                $normalizedMetadata[$lowerKey] = $value;

                // Create fallback aliases INSIDE the metadata array to match exact React expectations
                if (str_contains($lowerKey, 'techni')) {
                    $normalizedMetadata['technicien'] = $value;
                }
                if (str_contains($lowerKey, 'etat')) {
                    $normalizedMetadata['etat'] = $value;
                }
                if (str_contains($lowerKey, 'motif')) {
                    $normalizedMetadata['motif'] = $value;
                }
                if (str_contains($lowerKey, 'comment')) {
                    $normalizedMetadata['commentaire'] = $value;
                }
                if (str_contains($lowerKey, 'date')) {
                    // React specifically asks for this exact string in HIGHLIGHTED_METADATA_COLUMNS
                    $normalizedMetadata["date d'intervention"] = $value;
                }
            }
        }

        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'category'    => $this->category,
            'file_path'   => $this->filePath,
            'metadata'    => $normalizedMetadata, // Pass the aliases back into the metadata box!
            'status'      => $this->status,
            'created_by'  => $this->createdBy,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
        ];
    }

    /**
     * This forces the PHP JSON encoder to use your custom toArray() format 
     * when communicating with your React frontend API.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}