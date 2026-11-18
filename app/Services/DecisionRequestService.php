<?php

namespace App\Services;

use App\Models\Note;
use App\Models\User;
use App\Notifications\DecisionRequestNotification;

class DecisionRequestService
{
    public function promote(Note $note, User $decider): void
    {
        $note->notetype = 'decision_needed';
        $note->decider_id = $decider->id;
        $note->save();

        $this->notify($note);
    }

    public function notify(Note $note): void
    {
        $note->loadMissing('decider', 'user');

        if (! $note->decider || $note->decider->id === $note->user_id) {
            return;
        }

        $note->decider->notifyNow(new DecisionRequestNotification(
            $note->user->id,
            $note->user->name,
            $note->ticket_id,
            $note->id,
            trim(strip_tags($note->body_markdown ?: $note->body)),
            url("/tickets/{$note->ticket_id}#note_{$note->id}")
        ));
    }

    public function closeWith(Note $decision): void
    {
        if ($decision->notetype !== 'decision' || ! $decision->parent_id) {
            return;
        }

        $request = Note::query()->openDecisionRequests()->find($decision->parent_id);

        $request?->update([
            'resolved' => true,
            'resolved_by' => $decision->user_id,
            'resolution_message' => trim(strip_tags($decision->body_markdown ?: $decision->body)),
        ]);
    }
}
