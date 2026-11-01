<?php

namespace App\Services;

use App\Models\Mention;
use App\Models\Note;
use App\Models\TicketUserWatcher;
use App\Models\User;
use Illuminate\Support\Collection;

class MentionService
{
    public function findMentionedUser(string $markdown, ?string $name = null): ?User
    {
        if ($name !== null && trim($name) !== '') {
            return $this->findUser(ltrim(trim($name), '@'));
        }

        $users = $this->resolveUsers($markdown);

        return $users->count() === 1 ? $users->first() : null;
    }

    /**
     * @return Collection<int, User>
     */
    public function resolveUsers(string $markdown): Collection
    {
        return collect($this->parseMentions($markdown))
            ->map(fn (string $name) => $this->findUser($name))
            ->filter()
            ->unique('id')
            ->values();
    }

    public function findUser(string $name): ?User
    {
        $name = preg_replace('/\s*\([^)]*\)$/', '', trim($name));

        $candidates = User::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->get();

        return $candidates->firstWhere('name', $name) ?? $candidates->first();
    }

    public function parseMentions(string $markdown): array
    {
        preg_match_all('/@\[([^\]]+)\]/u', $markdown, $bracketMatches);

        // The lookbehind keeps email addresses such as support@example.com
        // from being read as a bare mention of "example.com".
        preg_match_all('/(?<![\w.])@([\w.\-]+)/u', $markdown, $bareMatches);

        $bracketMentions = array_values(array_unique(array_map(
            fn (string $token) => preg_replace('/\s*\([^)]*\)$/', '', trim($token)),
            $bracketMatches[1] ?? []
        )));

        $bareMentions = array_values(array_unique($bareMatches[1] ?? []));

        return array_unique(array_merge($bracketMentions, $bareMentions));
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
