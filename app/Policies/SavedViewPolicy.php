<?php

namespace App\Policies;

use App\Models\SavedView;
use App\Models\User;

class SavedViewPolicy
{
    public function delete(User $user, SavedView $view): bool
    {
        return $view->user_id === $user->id;
    }
}
