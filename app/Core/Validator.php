<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        foreach ($rules as $field => $ruleList) {
            $rulesArray = is_string($ruleList) ? explode('|', $ruleList) : $ruleList;
            $value = $data[$field] ?? null;
            $strVal = is_scalar($value) ? trim((string) $value) : '';

            if (in_array('nullable', $rulesArray, true) && $strVal === '' && !is_array($value)) {
                continue;
            }

            foreach ($rulesArray as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }
                if ($rule === 'required') {
                    if ($value === null || (is_string($value) && trim($value) === '') || (is_array($value) && $value === [])) {
                        $this->addError($field, Translator::get('validation.required', ['field' => $field]));
                        break;
                    }
                } elseif (str_starts_with($rule, 'min:')) {
                    $min = (int) substr($rule, 4);
                    if (mb_strlen($strVal) < $min) {
                        $this->addError($field, Translator::get('validation.min', ['field' => $field, 'min' => $min]));
                    }
                } elseif (str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                    if (mb_strlen($strVal) > $max) {
                        $this->addError($field, Translator::get('validation.max', ['field' => $field, 'max' => $max]));
                    }
                } elseif ($rule === 'email') {
                    if ($strVal !== '' && !filter_var($strVal, FILTER_VALIDATE_EMAIL)) {
                        $this->addError($field, Translator::get('validation.email', ['field' => $field]));
                    }
                } elseif ($rule === 'numeric') {
                    if ($strVal !== '' && !is_numeric($strVal)) {
                        $this->addError($field, Translator::get('validation.numeric', ['field' => $field]));
                    }
                } elseif ($rule === 'integer') {
                    if ($strVal !== '' && filter_var($strVal, FILTER_VALIDATE_INT) === false) {
                        $this->addError($field, Translator::get('validation.integer', ['field' => $field]));
                    }
                } elseif (str_starts_with($rule, 'in:')) {
                    $allowed = explode(',', substr($rule, 3));
                    if ($strVal !== '' && !in_array($strVal, $allowed, true)) {
                        $this->addError($field, Translator::get('validation.in', ['field' => $field]));
                    }
                } elseif (str_starts_with($rule, 'min_val:')) {
                    $minVal = (float) substr($rule, 8);
                    if (is_numeric($strVal) && (float) $strVal < $minVal) {
                        $this->addError($field, Translator::get('validation.min_val', ['field' => $field, 'min' => $minVal]));
                    }
                } elseif (str_starts_with($rule, 'max_val:')) {
                    $maxVal = (float) substr($rule, 8);
                    if (is_numeric($strVal) && (float) $strVal > $maxVal) {
                        $this->addError($field, Translator::get('validation.max_val', ['field' => $field, 'max' => $maxVal]));
                    }
                }
            }
        }

        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            return $messages[0] ?? null;
        }
        return null;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
