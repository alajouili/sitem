<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\Request;
use App\Core\Validator;
use App\Exceptions\ValidationException;

final class LoginRequest
{
    public readonly string $email;
    public readonly string $password;

    private function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public static function fromRequest(Request $request): self
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $data = $validator->validated();

        return new self($data['email'], $data['password']);
    }
}