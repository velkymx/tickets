<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaletteSearchRequest;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class PaletteController extends Controller
{
    public function __invoke(PaletteSearchRequest $request): JsonResponse
    {
        $query = trim((string) $request->validated()['q'] ?? '');

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        // Deliberately global to authenticated users, matching the web
        // ticket index and TicketPolicy::view (both allow any signed-in
        // team member to read any ticket). Scoping only the palette
        // while those stay open would be theater, not security. If a
        // per-ticket visibility model ever lands, scope here too.
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
