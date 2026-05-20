<?php

namespace App\Automations\Evaluators;

class ConditionEvaluator
{
    public function matches(array $conditions, array $context): bool
    {
        if (isset($conditions['all'])) {
            foreach ($conditions['all'] as $condition) {
                if (! $this->matches($condition, $context)) {
                    return false;
                }
            }
            return true;
        }

        if (isset($conditions['any'])) {
            foreach ($conditions['any'] as $condition) {
                if ($this->matches($condition, $context)) {
                    return true;
                }
            }
            return false;
        }

        return $this->evaluateLeaf($conditions, $context);
    }

    private function evaluateLeaf(array $condition, array $context): bool
    {
        $operator = $condition['operator'] ?? null;
        if (! $operator) {
            return false;
        }
        $target = $condition['value'] ?? null;

        if ($operator === 'changed') {
            return array_key_exists($condition['field'], $context['changes'] ?? []);
        }

        $value = data_get($context, $condition['field']);

        return match ($operator) {
            'equals'       => $value == $target,
            'not_equals'   => $value != $target,
            'contains'     => str_contains((string) $value, (string) $target),
            'starts_with'  => str_starts_with((string) $value, (string) $target),
            'ends_with'    => str_ends_with((string) $value, (string) $target),
            'greater_than' => $value > $target,
            'less_than'    => $value < $target,
            'in'           => in_array($value, (array) $target),
            'not_in'       => ! in_array($value, (array) $target),
            'is_empty'     => empty($value),
            'is_not_empty' => ! empty($value),
            default        => false,
        };
    }
}
