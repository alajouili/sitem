<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\User;

final class UserResource
{
    private function __construct(private readonly User $user)
    {
    }

    public static function make(User $user): self
    {
        return new self($user);
    }

    public function toArray(): array
    {
        // User::toArray() already omits password_hash by design.
        return $this->user->toArray();
    }

    /**
     * @param User[] $users
     */
    public static function collection(array $users): array
    {
        return array_map(fn (User $u) => self::make($u)->toArray(), $users);
    }
}