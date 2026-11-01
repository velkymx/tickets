<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaletteSearchRequest;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class PaletteController extends Controller
{
    public function __invoke(PaletteSearchRequest $request): JsonResponse
    {
        $query = trim((string) $request->validated('q'));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        // Global to authenticated users, matching the ticket index and
        // TicketPolicy::view. If per-ticket visibility lands, scope here too.
        $tickets = Ticket::filter(['q' => $query])
            ->latest('updated_at')
            ->limit(8)
            ->get(['id', 'subject']);

        return response()->json(
            $tickets->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'url' => url("/tickets/{$ticket->id}"),
            ])
        );
    }
}
