<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight rule-based validator, in the spirit of Laravel's validator
 * but dependency-free. Used directly or wrapped by
 * app/Services/ValidationService.php and the *Request classes.
 *
 * Usage:
 *   $validator = Validator::make($data, [
 *       'email'    => 'required|email',
 *       'password' => 'required|string|min:8',
 *       'role'     => 'required|in:admin,editor,viewer',
 *   ]);
 *
 *   if ($validator->fails()) {
 *       throw new ValidationException($validator->errors());
 *   }
 *
 *   $clean = $validator->validated();
 */
final class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private ?array $validated = null;

    private function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        $instance = new self($data, $rules);
        $instance->run();

        return $instance;
    }

    private function run(): void
    {
        $validated = [];

        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            $nullable = in_array('nullable', $rules, true);
            if ($nullable && ($value === null || $value === '')) {
                $validated[$field] = $value;
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                $this->applyRule($field, $value, $rule);
            }

            if (!isset($this->errors[$field]) && array_key_exists($field, $this->data)) {
                $validated[$field] = $value;
            }
        }

        $this->validated = $validated;
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

        $passed = match ($name) {
            'required' => $this->isPresent($value),
            'string'   => $value === null || is_string($value),
            'numeric'  => $value === null || is_numeric($value),
            'integer'  => $value === null || filter_var($value, FILTER_VALIDATE_INT) !== false,
            'boolean'  => $value === null || in_array($value, [true, false, 0, 1, '0', '1'], true),
            'array'    => $value === null || is_array($value),
            'email'    => $value === null || filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url'      => $value === null || filter_var($value, FILTER_VALIDATE_URL) !== false,
            'date'     => $value === null || strtotime((string) $value) !== false,
            'min'      => $this->checkMin($value, $param),
            'max'      => $this->checkMax($value, $param),
            'in'       => $value === null || in_array((string) $value, explode(',', (string) $param), true),
            'regex'    => $value === null || preg_match((string) $param, (string) $value) === 1,
            'confirmed' => $value === ($this->data["{$field}_confirmation"] ?? null),
            default    => true,
        };

        if (!$passed) {
            $this->addError($field, $this->message($field, $name, $param));
        }
    }

    private function isPresent(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && count($value) === 0) {
            return false;
        }

        return true;
    }

    private function checkMin(mixed $value, ?string $param): bool
    {
        if ($value === null || $param === null) {
            return true;
        }

        if (is_numeric($value)) {
            return (float) $value >= (float) $param;
        }

        if (is_array($value)) {
            return count($value) >= (int) $param;
        }

        return mb_strlen((string) $value) >= (int) $param;
    }

    private function checkMax(mixed $value, ?string $param): bool
    {
        if ($value === null || $param === null) {
            return true;
        }

        if (is_numeric($value)) {
            return (float) $value <= (float) $param;
        }

        if (is_array($value)) {
            return count($value) <= (int) $param;
        }

        return mb_strlen((string) $value) <= (int) $param;
    }

    private function message(string $field, string $rule, ?string $param): string
    {
        return match ($rule) {
            'required'  => "The {$field} field is required.",
            'string'    => "The {$field} field must be a string.",
            'numeric'   => "The {$field} field must be numeric.",
            'integer'   => "The {$field} field must be an integer.",
            'boolean'   => "The {$field} field must be true or false.",
            'array'     => "The {$field} field must be an array.",
            'email'     => "The {$field} field must be a valid email address.",
            'url'       => "The {$field} field must be a valid URL.",
            'date'      => "The {$field} field must be a valid date.",
            'min'       => "The {$field} field must be at least {$param}.",
            'max'       => "The {$field} field must not be greater than {$param}.",
            'in'        => "The selected {$field} is invalid.",
            'regex'     => "The {$field} field format is invalid.",
            'confirmed' => "The {$field} confirmation does not match.",
            default     => "The {$field} field is invalid.",
        };
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return !$this->fails();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->validated ?? [];
    }
}