<?php

declare(strict_types=1);

namespace App\Validators;

use App\Models\User;

final class UserValidator
{
    public static function rulesForCreate(): array
    {
        return [
            'name'     => 'required|string|min:2|max:150',
            'email'    => 'required|email|max:190',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:' . implode(',', User::ROLES),
        ];
    }

    public static function rulesForUpdate(): array
    {
        return [
            'name'     => 'nullable|string|min:2|max:150',
            'email'    => 'nullable|email|max:190',
            'password' => 'nullable|string|min:8',
            'role'     => 'nullable|in:' . implode(',', User::ROLES),
        ];
    }
}