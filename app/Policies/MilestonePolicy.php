<?php

namespace App\Policies;

use App\Models\Milestone;
use App\Models\User;

class MilestonePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Milestone $milestone): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Milestone $milestone): bool
    {
        return true;
    }

    public function delete(User $user, Milestone $milestone): bool
    {
        return true;
    }

    public function viewReport(User $user, Milestone $milestone): bool
    {
        return true;
    }

    public function watch(User $user, Milestone $milestone): bool
    {
        return true;
    }
}
