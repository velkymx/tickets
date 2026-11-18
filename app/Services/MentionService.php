<?php

namespace App\Services;

use App\Models\Mention;
use App\Models\Note;
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

    /**
     * @param  array<string, string>  $markdowns
     * @return array<string, ?User>
     */
    public function resolveFirstUsers(array $markdowns): array
    {
        $tokens = [];
        foreach ($markdowns as $key => $markdown) {
            $tokens[$key] = $this->parseMentions($markdown);
        }

        $names = collect($tokens)->flatten()->unique()->values();
        $users = $names->isEmpty() ? collect() : User::query()
            ->whereIn('name_lower', $names->map(fn (string $name) => mb_strtolower($name))->all())
            ->orderBy('id')
            ->get()
            ->groupBy('name_lower');

        $resolved = [];
        foreach ($tokens as $key => $names) {
            $resolved[$key] = null;
            foreach ($names as $name) {
                $candidates = $users->get(mb_strtolower($name), collect());
                $user = $candidates->firstWhere('name', $name) ?? $candidates->first();
                if ($user !== null) {
                    $resolved[$key] = $user;
                    break;
                }
            }
        }

        return $resolved;
    }

    public function findUser(string $name): ?User
    {
        $name = preg_replace('/\s*\([^)]*\)$/', '', trim($name));

        $candidates = User::query()->withName($name)->get();

        // Display names are not unique. Prefer the exact case, then the
        // oldest account, so resolution never depends on load order.
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

        Mention::query()->insertOrIgnore(array_map(
            fn (int $userId) => [
                'note_id' => $note->id,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $userIds
        ));
    }
}
