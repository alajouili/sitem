<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\AuditLog;
use PDO;

final class AuditLogRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function create(?int $userId, string $action, string $entityType, ?int $entityId, array $meta = []): AuditLog
    {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, meta)
             VALUES (:user_id, :action, :entity_type, :entity_id, :meta)'
        );

        $stmt->execute([
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'meta'        => json_encode($meta),
        ]);

        $id = (int) $this->db->lastInsertId();

        $find = $this->db->prepare('SELECT * FROM audit_logs WHERE id = :id LIMIT 1');
        $find->execute(['id' => $id]);

        return AuditLog::fromRow($find->fetch());
    }

    /**
     * @return array{data: AuditLog[], total: int}
     */
    public function paginate(int $page = 1, int $perPage = 50, ?string $entityType = null, ?int $entityId = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $clauses = [];
        $params = [];

        if ($entityType !== null) {
            $clauses[] = 'entity_type = :entity_type';
            $params['entity_type'] = $entityType;
        }
        if ($entityId !== null) {
            $clauses[] = 'entity_id = :entity_id';
            $params['entity_id'] = $entityId;
        }

        $where = empty($clauses) ? '' : 'WHERE ' . implode(' AND ', $clauses);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM audit_logs {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT * FROM audit_logs {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = array_map(fn ($row) => AuditLog::fromRow($row), $stmt->fetchAll());

        return ['data' => $data, 'total' => $total];
    }
}