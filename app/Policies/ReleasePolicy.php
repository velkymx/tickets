<?php

namespace App\Policies;

use App\Models\Release;
use App\Models\User;

class ReleasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Release $release): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Release $release): bool
    {
        return $user->id === $release->user_id;
    }

    public function delete(User $user, Release $release): bool
    {
        return $user->id === $release->user_id;
    }
}
