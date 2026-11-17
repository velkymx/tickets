<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class UniqueKbSlug implements ValidationRule
{
    public function __construct(
        private string $model,
        private ?int $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $slug = Str::slug((string) $value);

        if ($slug === '') {
            $fail('The name must contain at least one letter or number.');

            return;
        }

        $query = $this->model::query()->where('slug', $slug);

        if ($this->ignoreId !== null) {
            $query->whereKeyNot($this->ignoreId);
        }

        if ($query->exists()) {
            $fail('A record with a similar name already exists.');
        }
    }
}
