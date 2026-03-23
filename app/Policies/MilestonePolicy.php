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
        if (! $milestone->scrummaster_user_id && ! $milestone->owner_user_id) {
            return false;
        }

        return $milestone->scrummaster_user_id === $user->id
            || $milestone->owner_user_id === $user->id;
    }

    public function delete(User $user, Milestone $milestone): bool
    {
        if (! $milestone->owner_user_id) {
            return false;
        }

        return $milestone->owner_user_id === $user->id;
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
