<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SearchableRepository
{
    public function search(string $query, ?User $user = null, array $filters = []): LengthAwarePaginator;
}
