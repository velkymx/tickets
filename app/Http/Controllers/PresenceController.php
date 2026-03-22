<?php

namespace App\Http\Controllers;

use App\Services\PresenceService;
use Illuminate\Support\Facades\Auth;

class PresenceController extends Controller
{
    public function heartbeat(int $ticketId, PresenceService $presence)
    {
        $presence->updatePresence($ticketId, Auth::user());
        $viewers = $presence->getViewers($ticketId);

        return response()->json([
            'viewers' => $viewers,
            'count' => count($viewers),
        ]);
    }

    public function show(int $ticketId, PresenceService $presence)
    {
        $viewers = $presence->getViewers($ticketId);

        return response()->json([
            'viewers' => $viewers,
            'count' => count($viewers),
        ]);
    }
}
