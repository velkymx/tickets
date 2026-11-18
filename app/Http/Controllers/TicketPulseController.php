<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Ticket;
use App\Services\PresenceService;
use App\Services\TicketPulseService;
use Illuminate\Support\Facades\Auth;

class TicketPulseController extends Controller
{
    public function show(Ticket $ticket, TicketPulseService $pulseService, PresenceService $presenceService)
    {
        $this->authorize('view', $ticket);

        $presenceService->updatePresence($ticket->id, Auth::user());

        $pulse = $pulseService->getPulse($ticket)->toArray();
        $pulse['viewers'] = $presenceService->getViewers($ticket->id);
        $pulse['resolvable_note_ids'] = $ticket->notes()
            ->where('hide', 0)
            ->get(['id', 'ticket_id', 'user_id', 'decider_id'])
            ->filter(function (Note $note) use ($ticket) {
                $note->setRelation('ticket', $ticket);

                return Auth::user()->can('resolve', $note);
            })
            ->pluck('id')
            ->values()
            ->all();

        return response()->json($pulse);
    }
}
