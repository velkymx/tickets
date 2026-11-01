<?php

namespace App\Services;

use App\Models\Note;

class NotePromotionService
{
    public function __construct(
        private MentionService $mentionService,
        private MarkdownService $markdownService,
        private DecisionRequestService $decisionRequests,
    ) {}

    public function promote(Note $note, string $type, ?string $assignee = null): ?string
    {
        if ($note->notetype !== 'message') {
            return 'Only message notes can be promoted.';
        }

        if ($type === 'decision' && mb_strlen(trim(strip_tags($note->body))) < 20) {
            return 'Decision notes must be at least 20 characters.';
        }

        if ($type !== 'action' && $type !== 'decision_needed') {
            $note->notetype = $type;
            $note->save();

            return null;
        }

        $markdown = $note->body_markdown ?: $note->body;
        $user = $this->mentionService->findMentionedUser($markdown, $assignee);

        if (! $user) {
            return $type === 'action'
                ? 'Actions require exactly one @assignee.'
                : 'Decision requests require exactly one @decider.';
        }

        if ($type === 'decision_needed') {
            $this->decisionRequests->promote($note, $user);

            return null;
        }

        if (! $this->mentionService->resolveUsers($markdown)->contains('id', $user->id)) {
            $markdown = rtrim($markdown).' @['.$user->name.']';
            $note->body_markdown = $markdown;
            $note->body = $this->markdownService->parse($markdown);
        }

        $note->assignee_id = $user->id;
        $note->notetype = 'action';
        $note->save();

        $this->mentionService->createMentions($note, [$user->id]);

        return null;
    }
}
