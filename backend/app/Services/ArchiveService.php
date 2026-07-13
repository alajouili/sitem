<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\Archive;
use App\Repositories\ArchiveRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\ImageRepository;
use App\Storage\StorageInterface;

final class ArchiveService
{
    public function __construct(
        private readonly ArchiveRepository $archives = new ArchiveRepository(),
        private readonly ImageRepository $images = new ImageRepository(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ?StorageInterface $storage = null
    ) {
    }

    public function find(int $id): Archive
    {
        $archive = $this->archives->findById($id);

        if ($archive === null) {
            throw new NotFoundException("Archive #{$id} not found.");
        }

        return $archive;
    }

    public function list(int $page, int $perPage, array $filters): array
    {
        return $this->archives->paginate($page, $perPage, $filters);
    }

    public function create(array $data, ?int $userId): Archive
    {
        $archive = $this->archives->create(
            title: $data['title'],
            description: $data['description'] ?? null,
            category: $data['category'] ?? null,
            filePath: $data['file_path'] ?? null,
            metadata: $data['metadata'] ?? [],
            status: $data['status'] ?? Archive::STATUS_DRAFT,
            createdBy: $userId,
        );

        $this->auditLogs->create($userId, 'archive.created', 'archive', $archive->id, ['title' => $archive->title]);

        return $archive;
    }

    public function update(int $id, array $data, ?int $userId): Archive
    {
        $existing = $this->find($id);

        $updated = $this->archives->update($id, $data) ?? $existing;

        $this->auditLogs->create($userId, 'archive.updated', 'archive', $id, ['fields' => array_keys($data)]);

        return $updated;
    }

    public function delete(int $id, ?int $userId): void
    {
        $archive = $this->find($id);

        foreach ($this->images->findByArchiveId($id) as $image) {
            $this->storage?->delete($image->path);
        }
        $this->images->deleteByArchiveId($id);

        if ($archive->filePath !== null) {
            $this->storage?->delete($archive->filePath);
        }

        $this->archives->delete($id);

        $this->auditLogs->create($userId, 'archive.deleted', 'archive', $id, ['title' => $archive->title]);
    }

    /**
     * Deletes every archive (and their images/files), one at a time so
     * each gets the same storage cleanup and audit log entry as a single
     * delete. Intended for clearing out a botched import; not exposed
     * for casual use — the route requires admin + explicit confirmation
     * client-side.
     *
     * @return int number of archives deleted
     */
    public function deleteAll(?int $userId): int
    {
        $ids = $this->archives->allIds();

        foreach ($ids as $id) {
            $this->delete($id, $userId);
        }

        return count($ids);
    }

    /**
     * @return array<int, \App\Models\Image>
     */
    public function imagesFor(int $archiveId): array
    {
        $this->find($archiveId); // 404s if the archive doesn't exist

        return $this->images->findByArchiveId($archiveId);
    }
}