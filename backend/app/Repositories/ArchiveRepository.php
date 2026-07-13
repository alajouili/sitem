<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Archive;
use PDO;

final class ArchiveRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function findById(int $id): ?Archive
    {
        $stmt = $this->db->prepare('SELECT * FROM archives WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Archive::fromRow($row) : null;
    }

    /**
     * @param array{category?:string, status?:string, search?:string} $filters
     * @return array{data: Archive[], total: int}
     */
    public function paginate(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        [$where, $params] = $this->buildWhere($filters);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM archives {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT * FROM archives {$where} ORDER BY id ASC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = array_map(fn ($row) => Archive::fromRow($row), $stmt->fetchAll());

        return ['data' => $data, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    private function buildWhere(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['category'])) {
            $clauses[] = 'category = :category';
            $params['category'] = $filters['category'];
        }

        if (!empty($filters['status'])) {
            $clauses[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['created_by'])) {
            $clauses[] = 'created_by = :created_by';
            $params['created_by'] = $filters['created_by'];
        }

        if (!empty($filters['search'])) {
            // LIKE-based fallback that works identically on MySQL/SQLite;
            // MySQL deployments may prefer MATCH...AGAINST via the
            // ft_archives_search index for larger datasets.
            $clauses[] = '(title LIKE :search OR description LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $where = empty($clauses) ? '' : 'WHERE ' . implode(' AND ', $clauses);

        return [$where, $params];
    }

    public function create(
        string $title,
        ?string $description,
        ?string $category,
        ?string $filePath,
        array $metadata,
        string $status,
        ?int $createdBy
    ): Archive {
        $stmt = $this->db->prepare(
            'INSERT INTO archives (title, description, category, file_path, metadata, status, created_by)
             VALUES (:title, :description, :category, :file_path, :metadata, :status, :created_by)'
        );

        $stmt->execute([
            'title'       => $title,
            'description' => $description,
            'category'    => $category,
            'file_path'   => $filePath,
            'metadata'    => json_encode($metadata),
            'status'      => $status,
            'created_by'  => $createdBy,
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function update(int $id, array $fields): ?Archive
    {
        $allowed = array_intersect_key($fields, array_flip([
            'title', 'description', 'category', 'file_path', 'metadata', 'status',
        ]));

        if (empty($allowed)) {
            return $this->findById($id);
        }

        if (array_key_exists('metadata', $allowed) && is_array($allowed['metadata'])) {
            $allowed['metadata'] = json_encode($allowed['metadata']);
        }

        $assignments = implode(', ', array_map(fn ($col) => "{$col} = :{$col}", array_keys($allowed)));
        $stmt = $this->db->prepare("UPDATE archives SET {$assignments} WHERE id = :id");
        $stmt->execute([...$allowed, 'id' => $id]);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM archives WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    /**
     * @return int[] every archive id currently in the table
     */
    public function allIds(): array
    {
        $stmt = $this->db->query('SELECT id FROM archives ORDER BY id ASC');

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Deletes every archive row. Does NOT touch images/storage — callers
     * (ArchiveService::deleteAll) are responsible for cleaning those up
     * per-archive first, the same way single delete() does.
     */
    public function deleteAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM archives');
        $count = (int) $stmt->fetchColumn();

        $this->db->exec('DELETE FROM archives');

        return $count;
    }

    public function countByStatus(): array
    {
        $stmt = $this->db->query('SELECT status, COUNT(*) as total FROM archives GROUP BY status');

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}