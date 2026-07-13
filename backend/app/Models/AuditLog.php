<?php

declare(strict_types=1);

namespace App\Models;

final class AuditLog
{
    public ?int $id;
    public ?int $userId;
    public string $action;
    public string $entityType;
    public ?int $entityId;
    public array $meta;
    public ?string $createdAt;

    public function __construct(
        ?int $id,
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId,
        array $meta = [],
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->action = $action;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->meta = $meta;
        $this->createdAt = $createdAt;
    }

    public static function fromRow(array $row): self
    {
        $meta = $row['meta'] ?? '[]';
        if (is_string($meta)) {
            $meta = json_decode($meta, true) ?: [];
        }

        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            userId: isset($row['user_id']) ? (int) $row['user_id'] : null,
            action: (string) $row['action'],
            entityType: (string) $row['entity_type'],
            entityId: isset($row['entity_id']) ? (int) $row['entity_id'] : null,
            meta: $meta,
            createdAt: $row['created_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->userId,
            'action'      => $this->action,
            'entity_type' => $this->entityType,
            'entity_id'   => $this->entityId,
            'meta'        => $this->meta,
            'created_at'  => $this->createdAt,
        ];
    }
}