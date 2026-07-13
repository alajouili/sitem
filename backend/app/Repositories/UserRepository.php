<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;

final class UserRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? User::fromRow($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? User::fromRow($row) : null;
    }

    public function existsByEmail(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * @return array{data: User[], total: int}
     */
    public function paginate(int $page = 1, int $perPage = 20, ?string $role = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];
        if ($role !== null) {
            $where = 'WHERE role = :role';
            $params['role'] = $role;
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT * FROM users {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = array_map(fn ($row) => User::fromRow($row), $stmt->fetchAll());

        return ['data' => $data, 'total' => $total];
    }

    public function create(string $name, string $email, string $passwordHash, string $role = User::ROLE_VIEWER): User
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
        );
        $stmt->execute([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $passwordHash,
            'role'          => $role,
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function update(int $id, array $fields): ?User
    {
        $allowed = array_intersect_key($fields, array_flip(['name', 'email', 'password_hash', 'role']));

        if (empty($allowed)) {
            return $this->findById($id);
        }

        $assignments = implode(', ', array_map(fn ($col) => "{$col} = :{$col}", array_keys($allowed)));
        $stmt = $this->db->prepare("UPDATE users SET {$assignments} WHERE id = :id");

        $stmt->execute([...$allowed, 'id' => $id]);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }
}