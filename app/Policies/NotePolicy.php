<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NotePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function update(User $user, Note $note): Response
    {
        return $user->id === $note->user_id
            ? Response::allow()
            : Response::deny('Only the author can edit this note.');
    }

    public function hide(User $user, Note $note): Response
    {
        return $this->involved($user, $note)
            ? Response::allow()
            : Response::deny('You cannot hide this note');
    }

    public function promote(User $user, Note $note): Response
    {
        return $this->involved($user, $note)
            ? Response::allow()
            : Response::deny('You cannot promote this note');
    }

    public function pin(User $user, Note $note): Response
    {
        return $this->involved($user, $note)
            ? Response::allow()
            : Response::deny('You cannot pin this note');
    }

    public function attach(User $user, Note $note): Response
    {
        return $this->involved($user, $note)
            ? Response::allow()
            : Response::deny('You cannot attach files to this note');
    }

    public function resolve(User $user, Note $note): Response
    {
        return $this->involved($user, $note) || $user->id === $note->decider_id
            ? Response::allow()
            : Response::deny('Only the thread author, ticket creator, assignee, or decider can resolve.');
    }

    private function involved(User $user, Note $note): bool
    {
        return $user->id === $note->user_id
            || $user->id === $note->ticket->user_id
            || $user->id === $note->ticket->user_id2;
    }
}
