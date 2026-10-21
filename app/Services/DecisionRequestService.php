<?php

namespace App\Services;

use App\Models\Note;
use App\Models\User;

class DecisionRequestService
{
    public function __construct(private MentionService $mentionService) {}

    public function findDecider(string $text, ?string $assignee = null): ?User
    {
        if ($assignee !== null && trim($assignee) !== '') {
            return $this->findUser(ltrim(trim($assignee), '@'));
        }

        $names = $this->mentionService->parseMentions($text);

        return count($names) === 1 ? $this->findUser(reset($names)) : null;
    }

    public function promote(Note $note, User $decider): void
    {
        $note->notetype = 'decision_needed';
        $note->decider_id = $decider->id;
        $note->save();
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

    private function findUser(string $name): ?User
    {
        $name = preg_replace('/\s*\([^)]*\)$/', '', trim($name));

        $candidates = User::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->get();

        return $candidates->firstWhere('name', $name) ?? $candidates->first();
    }
}
