<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\Request;
use App\Core\Validator;
use App\Exceptions\ValidationException;
use App\Validators\ArchiveValidator;

final class ArchiveRequest
{
    private function __construct(public readonly array $data)
    {
    }

    public static function forCreate(Request $request): self
    {
        return self::validate($request, ArchiveValidator::rulesForCreate());
    }

    public static function forUpdate(Request $request): self
    {
        return self::validate($request, ArchiveValidator::rulesForUpdate());
    }

    private static function validate(Request $request, array $rules): self
    {
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        return new self($validator->validated());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}