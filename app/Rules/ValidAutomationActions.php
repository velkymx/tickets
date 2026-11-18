<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidAutomationActions implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $actions = json_decode((string) $value, true);

        if (! is_array($actions) || array_is_list($actions) === false) {
            $fail('The actions must be a JSON list of actions.');

            return;
        }

        $allowed = array_keys(config('automations.action_types', []));

        foreach ($actions as $action) {
            if (! is_array($action) || ! in_array($action['type'] ?? null, $allowed, true)) {
                $fail('Each action needs a known type: '.implode(', ', $allowed).'.');

                return;
            }

            if (! is_array($action['config'] ?? null)) {
                $fail('Each action needs a config object.');

                return;
            }

            if (in_array($action['type'], ['call_webhook', 'call_api'], true)) {
                $url = $action['config']['url'] ?? null;

                if (! is_string($url) || trim($url) === '') {
                    $fail('Webhook actions need a url in their config.');

                    return;
                }
            }
        }
    }
}
