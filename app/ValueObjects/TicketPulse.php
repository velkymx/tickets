<?php

namespace App\ValueObjects;

use JsonSerializable;

class TicketPulse implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $status,
        public readonly bool $is_blocked,
        public readonly ?string $blocker_reason,
        public readonly ?int $owner_id,
        public readonly string $owner_label,
        public readonly ?array $next_action,
        public readonly ?array $latest_decision,
        public readonly string $execution_state,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'is_blocked' => $this->is_blocked,
            'blocker_reason' => $this->blocker_reason,
            'owner_id' => $this->owner_id,
            'owner_label' => $this->owner_label,
            'next_action' => $this->next_action,
            'latest_decision' => $this->latest_decision,
            'execution_state' => $this->execution_state,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
