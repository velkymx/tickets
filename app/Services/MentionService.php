<?php

namespace App\Services;

use App\Models\Mention;
use App\Models\Note;
use App\Models\TicketUserWatcher;

class MentionService
{
    public function parseMentions(string $markdown): array
    {
        preg_match_all('/(?<![\w])@([\w.\-]+)/u', $markdown, $matches);

        return array_values(array_unique(array_map(
            fn (string $username) => rtrim($username, '.,;:!?'),
            $matches[1] ?? []
        )));
    }

    public function createMentions(Note $note, array $userIds): void
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        if ($userIds === []) {
            return;
        }

        $watcherIds = TicketUserWatcher::query()
            ->where('ticket_id', $note->ticket_id)
            ->whereIn('user_id', $userIds)
            ->pluck('user_id')
            ->all();

        $mentionIds = array_values(array_diff($userIds, $watcherIds));

        if ($mentionIds === []) {
            return;
        }

        Mention::query()->insertOrIgnore(array_map(
            fn (int $userId) => [
                'note_id' => $note->id,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $mentionIds
        ));
    }
}
