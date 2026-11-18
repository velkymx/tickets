<?php

namespace App\Models\Concerns;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

trait ReportsUniqueSlugCollisions
{
    public static function slugIsTaken(string $slug, ?int $ignoreId = null): bool
    {
        return static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    public function save(array $options = [])
    {
        try {
            return parent::save($options);
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'name' => $this->slugCollisionMessage(),
            ]);
        }
    }

    abstract protected function slugCollisionMessage(): string;
}
