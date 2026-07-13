<?php

declare(strict_types=1);

namespace App\Validators;

use App\Models\Archive;

final class ArchiveValidator
{
    public static function rulesForCreate(): array
    {
        return [
            'title'       => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:5000',
            'category'    => 'nullable|string|max:100',
            'status'      => 'nullable|in:' . implode(',', Archive::STATUSES),
            'metadata'    => 'nullable|array',
        ];
    }

    public static function rulesForUpdate(): array
    {
        return [
            'title'       => 'nullable|string|min:2|max:255',
            'description' => 'nullable|string|max:5000',
            'category'    => 'nullable|string|max:100',
            'status'      => 'nullable|in:' . implode(',', Archive::STATUSES),
            'metadata'    => 'nullable|array',
        ];
    }
}