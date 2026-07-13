<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Image;
use PDO;

final class ImageRepository
{
	private PDO $db;
    
    
	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Database::connection();
	}
    public function create(...$args)
    {
        // 1. Catch the data whether it was sent as named parameters or a single array
        $data = (count($args) === 1 && isset($args[0]) && is_array($args[0])) ? $args[0] : $args;

        // 2. Convert camelCase to snake_case (e.g., 'archiveId' becomes 'archive_id') 
        // to perfectly match your database columns
        $insertData = [];
        foreach ($data as $key => $value) {
            $snakeKey = strtolower(preg_replace('/[A-Z]/', '_$0', $key));
            $insertData[$snakeKey] = $value;
        }

        // 3. Build and execute the dynamic SQL query
        $columns = implode(', ', array_keys($insertData));
        $placeholders = implode(', ', array_map(fn($k) => ':' . $k, array_keys($insertData)));

        $stmt = $this->db->prepare("INSERT INTO images ($columns) VALUES ($placeholders)");
        $stmt->execute($insertData);

        // 4. Return the new image record
        $id = (int) $this->db->lastInsertId();
        return $this->findById($id);
    }
	public function findById(int $id): ?Image
	{
		$stmt = $this->db->prepare('SELECT * FROM images WHERE id = :id LIMIT 1');
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch();

		return $row ? Image::fromRow($row) : null;
	}

	public function delete(int $id): bool
	{
		$stmt = $this->db->prepare('DELETE FROM images WHERE id = :id');

		return $stmt->execute(['id' => $id]);
	}
    public function findByArchiveId(int $archiveId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM images WHERE archive_id = :archive_id'
        );

        $stmt->execute([
            'archive_id' => $archiveId,
        ]);

        return array_map(
            fn($row) => Image::fromRow($row),
            $stmt->fetchAll()
        );
    }
    public function deleteByArchiveId(int $archiveId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM images WHERE archive_id = :archive_id'
        );

        return $stmt->execute([
            'archive_id' => $archiveId,
        ]);
    }
}
