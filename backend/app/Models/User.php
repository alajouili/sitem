<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Plain data object hydrated by UserRepository. Persistence lives in the
 * repository; this class just carries the shape + role constants.
 */
final class User
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_VIEWER = 'viewer';

    public const ROLES = [self::ROLE_ADMIN, self::ROLE_EDITOR, self::ROLE_VIEWER];

    public ?int $id;
    public string $name;
    public string $email;
    public string $passwordHash;
    public string $role;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(
        ?int $id,
        string $name,
        string $email,
        string $passwordHash,
        string $role = self::ROLE_VIEWER,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            name: (string) $row['name'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            role: (string) ($row['role'] ?? self::ROLE_VIEWER),
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Array form WITHOUT the password hash — safe to pass toward a
     * Resource/JSON response by default.
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->role,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}