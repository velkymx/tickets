<?php

namespace App\Automations\ContextBuilders;

use App\Automations\Contracts\ContextBuilderInterface;
use App\Events\TicketCreated;
use App\Models\User;

class TicketCreatedContextBuilder implements ContextBuilderInterface
{
    public function build(object $event): array
    {
        /** @var TicketCreated $event */
        $ticket = $event->ticket->loadMissing(['type', 'status', 'importance', 'project', 'milestone']);
        $actor = $event->actorId ? User::find($event->actorId) : null;

        return [
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status?->name,
                'type' => $ticket->type?->name,
                'priority' => $ticket->importance?->name,
                'project' => $ticket->project?->name,
                'milestone' => $ticket->milestone?->name,
            ],
            'actor' => $actor ? [
                'id' => $actor->id,
                'email' => $actor->email,
                'name' => $actor->name,
            ] : null,
            'changes' => [],
        ];
    }
}
