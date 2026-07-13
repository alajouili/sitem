<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Resources\ReportResource;
use App\Services\ReportService;

final class ReportController
{
    private readonly ReportService $reports;

    public function __construct(?ReportService $reports = null)
    {
        $this->reports = $reports ?? new ReportService();
    }

    public function summary(Request $request): Response
    {
        return Response::success(ReportResource::make($this->reports->summary())->toArray());
    }

    public function exportCsv(Request $request): Response
    {
        $filters = array_filter([
            'category' => $request->query('category'),
            'status'   => $request->query('status'),
        ]);

        $csv = $this->reports->archivesToCsv($filters);

        return Response::text($csv)
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="archives-export.csv"');
    }

    /**
     * Paginated audit trail. Backed by AuditLogRepository (already a
     * dependency of ReportService) rather than a dedicated controller,
     * since the mandated architecture doesn't list one — audit history
     * is a reporting concern here.
     */
    public function logs(Request $request): Response
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 50);
        $entityType = $request->query('entity_type');
        $entityId = $request->query('entity_id') !== null ? (int) $request->query('entity_id') : null;

        $result = $this->reports->logs($page, $perPage, $entityType, $entityId);

        return Response::success([
            'items' => array_map(fn ($log) => $log->toArray(), $result['data']),
            'total' => $result['total'],
        ]);
    }
}