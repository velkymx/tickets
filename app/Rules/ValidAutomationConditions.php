<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidAutomationConditions implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $conditions = json_decode((string) $value, true);

        if (! is_array($conditions) || $this->invalid($conditions)) {
            $fail('The conditions must be an all/any group or a leaf with an operator and a field.');
        }
    }

    private function invalid(mixed $conditions): bool
    {
        if (! is_array($conditions) || array_is_list($conditions)) {
            return true;
        }

        if (array_key_exists('all', $conditions) || array_key_exists('any', $conditions)) {
            $children = $conditions['all'] ?? $conditions['any'];

            if (! is_array($children)) {
                return true;
            }

            foreach ($children as $child) {
                if ($this->invalid($child)) {
                    return true;
                }
            }

            return false;
        }

        return ! is_string($conditions['operator'] ?? null)
            || ! in_array($conditions['operator'], config('automations.operators', []), true)
            || ! is_string($conditions['field'] ?? null)
            || trim($conditions['field']) === '';
    }
}
