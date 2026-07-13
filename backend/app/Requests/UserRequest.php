<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\Request;
use App\Core\Validator;
use App\Exceptions\ValidationException;
use App\Validators\UserValidator;

final class UserRequest
{
    private function __construct(public readonly array $data)
    {
    }

    public static function forCreate(Request $request): self
    {
        return self::validate($request, UserValidator::rulesForCreate());
    }

    public static function forUpdate(Request $request): self
    {
        return self::validate($request, UserValidator::rulesForUpdate());
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