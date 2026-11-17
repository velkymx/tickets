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

        $markdown = $note->body_markdown;
        $recordedMentions = null;

        if ($markdown === null) {
            // Legacy notes predate body_markdown and only store rendered
            // HTML, which bare-mention parsing cannot resolve to full
            // names. Their recorded mentions are the source of truth.
            // Stripped body text keeps stored markdown free of HTML.
            $recordedMentions = $note->mentions()->with('user')->get()->pluck('user')
                ->filter()->unique('id')->values();
            $markdown = trim(strip_tags($note->body));
            $user = $assignee !== null && trim($assignee) !== ''
                ? $this->mentionService->findUser(ltrim(trim($assignee), '@'))
                : ($recordedMentions->count() === 1 ? $recordedMentions->first() : null);
        } else {
            $user = $this->mentionService->findMentionedUser($markdown, $assignee);
        }

        if (! $user) {
            return $type === 'action'
                ? 'Actions require exactly one @assignee.'
                : 'Decision requests require exactly one @decider.';
        }

        if ($type === 'decision_needed') {
            $this->decisionRequests->promote($note, $user);

            return null;
        }

        $alreadyMentioned = $recordedMentions !== null
            ? $recordedMentions->contains('id', $user->id)
            : $this->mentionService->resolveUsers($markdown)->contains('id', $user->id);

        if (! $alreadyMentioned) {
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
