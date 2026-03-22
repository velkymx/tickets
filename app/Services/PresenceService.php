<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PresenceService
{
    protected const TTL = 30; // Seconds before expiration

    public function updatePresence(int $ticketId, User $user): void
    {
        $key = "ticket_presence:{$ticketId}";
        $presence = Cache::get($key, []);
        
        // Remove old entries for this user
        $presence = array_filter($presence, function ($viewer) use ($user) {
            return $viewer['user_id'] !== $user->id;
        });
        
        // Add new entry
        $presence[] = [
            'user_id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatarUrl(),
            'last_seen' => now()->timestamp,
        ];

        // Filter out expired entries
        $presence = $this->filterExpired($presence);
        
        Cache::put($key, $presence, 60); // Cache key lasts 60s, individual entries filtered manually
    }

    public function getViewers(int $ticketId): array
    {
        $key = "ticket_presence:{$ticketId}";
        $presence = Cache::get($key, []);
        
        return $this->filterExpired($presence);
    }

    protected function filterExpired(array $presence): array
    {
        $threshold = now()->subSeconds(self::TTL)->timestamp;
        
        return array_values(array_filter($presence, function ($viewer) use ($threshold) {
            return $viewer['last_seen'] > $threshold;
        }));
    }
}
