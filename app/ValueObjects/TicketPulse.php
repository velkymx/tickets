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
        public readonly array $next_action,
        public readonly ?array $latest_decision,
        public readonly array $open_threads,
        public readonly ?array $latest_blocker,
        public readonly string $execution_state,
        public readonly mixed $last_activity_at,
        public readonly bool $is_stale,
        public readonly ?string $staleness_message,
    ) {}

    public function withOwnerLabel(string $label): self
    {
        return new self(
            id: $this->id,
            status: $this->status,
            is_blocked: $this->is_blocked,
            blocker_reason: $this->blocker_reason,
            owner_id: $this->owner_id,
            owner_label: $label,
            next_action: $this->next_action,
            latest_decision: $this->latest_decision,
            open_threads: $this->open_threads,
            latest_blocker: $this->latest_blocker,
            execution_state: $this->execution_state,
            last_activity_at: $this->last_activity_at,
            is_stale: $this->is_stale,
            staleness_message: $this->staleness_message,
        );
    }

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
            'open_threads' => $this->open_threads,
            'latest_blocker' => $this->latest_blocker,
            'execution_state' => $this->execution_state,
            'last_activity_at' => $this->last_activity_at,
            'is_stale' => $this->is_stale,
            'staleness_message' => $this->staleness_message,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
