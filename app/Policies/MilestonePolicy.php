<?php

namespace App\Policies;

use App\Models\Milestone;
use App\Models\User;

class MilestonePolicy
{
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
        return $user->id === $milestone->scrummaster_user_id
            || $user->id === $milestone->owner_user_id;
    }

    public function delete(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->scrummaster_user_id
            || $user->id === $milestone->owner_user_id;
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
