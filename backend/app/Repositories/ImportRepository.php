<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Import;
use PDO;

final class ImportRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function findById(int $id): ?Import
    {
        $stmt = $this->db->prepare('SELECT * FROM imports WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Import::fromRow($row) : null;
    }

    /**
     * @return array{data: Import[], total: int}
     */
    public function paginate(int $page = 1, int $perPage = 20, ?int $userId = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];
        if ($userId !== null) {
            $where = 'WHERE user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM imports {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT * FROM imports {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = array_map(fn ($row) => Import::fromRow($row), $stmt->fetchAll());

        return ['data' => $data, 'total' => $total];
    }

    public function create(?int $userId, string $filename): Import
    {
        $stmt = $this->db->prepare(
            'INSERT INTO imports (user_id, filename, status) VALUES (:user_id, :filename, :status)'
        );
        $stmt->execute([
            'user_id'  => $userId,
            'filename' => $filename,
            'status'   => Import::STATUS_PENDING,
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function updateProgress(int $id, string $status, int $totalRows, int $processedRows, array $errorLog = []): ?Import
    {
        $stmt = $this->db->prepare(
            'UPDATE imports
             SET status = :status, total_rows = :total_rows, processed_rows = :processed_rows, error_log = :error_log
             WHERE id = :id'
        );

        $stmt->execute([
            'status'         => $status,
            'total_rows'     => $totalRows,
            'processed_rows' => $processedRows,
            'error_log'      => json_encode($errorLog),
            'id'             => $id,
        ]);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM imports WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }
}