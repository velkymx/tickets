<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\PresenceService;
use App\Services\TicketPulseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketPulseController extends Controller
{
    public function show(Ticket $ticket, TicketPulseService $pulseService, PresenceService $presenceService)
    {
        $presenceService->updatePresence($ticket->id, Auth::user());
        
        $pulse = $pulseService->getPulse($ticket)->toArray();
        $pulse['viewers'] = $presenceService->getViewers($ticket->id);
        
        return response()->json($pulse);
    }
}
