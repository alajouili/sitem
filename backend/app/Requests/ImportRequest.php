<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\Request;
use App\Core\Validator;
use App\Exceptions\ValidationException;
use App\Validators\ImportValidator;

final class ImportRequest
{
    private function __construct(
        public readonly array $data,
        public readonly ?array $file
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $validator = Validator::make($request->all(), ImportValidator::rulesForStore());

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $file = $request->file('file');
        ImportValidator::validateUploadedFile($file);

        return new self($validator->validated(), $file);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}