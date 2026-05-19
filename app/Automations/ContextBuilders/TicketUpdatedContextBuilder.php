<?php

namespace App\Automations\ContextBuilders;

use App\Automations\Contracts\ContextBuilderInterface;
use App\Events\TicketUpdated;
use App\Models\User;

class TicketUpdatedContextBuilder implements ContextBuilderInterface
{
    public function build(object $event): array
    {
        /** @var TicketUpdated $event */
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
            'changes' => $event->changes,
        ];
    }
}
