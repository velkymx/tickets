<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Ticket;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\TicketResource;
use App\Importer;

class ImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $milestones = \App\Milestone::orderBy('name')->where('end_at', null)->pluck('name', 'id');
        return view('import.index', compact('milestones'));
    }
    
    public function create(Request $request)
    {
        (new Importer())->call(
            (int) $request->milestoneId,
            $request->csv->path(),
            (string) $request->hasHeader,
        );
        return view('import.index');
    }
}
