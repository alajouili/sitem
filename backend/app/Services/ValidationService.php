<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Validator;
use App\Exceptions\ValidationException;

/**
 * Wraps Core\Validator for callers that aren't an HTTP Request (e.g.
 * ExcelImportService validating each parsed spreadsheet row against
 * ArchiveValidator's rules).
 */
final class ValidationService
{
    /**
     * Validates $data against $rules. Returns the validated/whitelisted
     * data on success; throws ValidationException on failure.
     */
    public static function validate(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        return $validator->validated();
    }

    /**
     * Same as validate(), but returns [bool $passed, array $errorsOrData]
     * instead of throwing — useful in batch contexts (e.g. import row
     * processing) where one bad row shouldn't abort the whole batch.
     */
    public static function tryValidate(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return [false, $validator->errors()];
        }

        return [true, $validator->validated()];
    }
}