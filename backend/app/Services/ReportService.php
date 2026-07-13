<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ArchiveRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\ImportRepository;

final class ReportService
{
    private readonly ArchiveRepository $archives;
    private readonly ImportRepository $imports;
    private readonly AuditLogRepository $auditLogs;

    public function __construct(
        ?ArchiveRepository $archives = null,
        ?ImportRepository $imports = null,
        ?AuditLogRepository $auditLogs = null
    ) {
        $this->archives = $archives ?? new ArchiveRepository();
        $this->imports = $imports ?? new ImportRepository();
        $this->auditLogs = $auditLogs ?? new AuditLogRepository();
    }

    /**
     * High-level counts for a dashboard summary card.
     */
    public function summary(): array
    {
        $byStatus = $this->archives->countByStatus();

        return [
            'archives_by_status' => $byStatus,
            'archives_total'     => array_sum($byStatus),
            'generated_at'       => date(DATE_ATOM),
        ];
    }

    /**
     * Builds a CSV export of every archive matching $filters. Returns raw
     * CSV text — the controller decides how to send it (attachment
     * headers, streamed to storage/exports, etc).
     */
    public function archivesToCsv(array $filters = []): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['ID', 'Title', 'Category', 'Status', 'Created At']);

        $page = 1;
        do {
            $result = $this->archives->paginate($page, 100, $filters);
            foreach ($result['data'] as $archive) {
                fputcsv($handle, [
                    $archive->id,
                    $archive->title,
                    $archive->category,
                    $archive->status,
                    $archive->createdAt,
                ]);
            }
            $page++;
        } while (($page - 1) * 100 < $result['total']);

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * @return array{data: \App\Models\AuditLog[], total: int}
     */
    public function logs(int $page = 1, int $perPage = 50, ?string $entityType = null, ?int $entityId = null): array
    {
        return $this->auditLogs->paginate($page, $perPage, $entityType, $entityId);
    }
}