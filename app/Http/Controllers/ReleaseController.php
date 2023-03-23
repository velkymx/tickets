<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Release;
use App\Models\ReleaseTicket;
use App\Models\Type;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Console\Presets\React;

class ReleaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {     

        $releases = Release::all();

        return View('release/index',compact('releases'));
        
    }

    public function create()
    {
        return View('release/create');
    }

    public function edit(Request $request)
    {

        $release = Release::findOrFail($request->id);

        return View('release/edit',compact('release'));
    }    

    public function put(Request $request)
    {

        $release = Release::findOrFail($request->id);

        $release->title = $request->title;
        $release->body = $request->body;

        if ($request->started_at <> '') {
            $release->started_at = date('Y-m-d', strtotime($request->started_at));
        } else {
            $release->started_at = '';
        }   
        
        if ($request->completed_at <> '') {
            $release->completed_at = date('Y-m-d', strtotime($request->completed_at));
        } else {
            $release->completed_at = '';
        }           

        $release->save();

        \Session::flash('info_message', 'Release Saved');

        return redirect('release/' . $release->id);    
    }        

    public function show(Request $request)
    {
        $release = Release::findOrFail($request->id);

        $release_tickets = ReleaseTicket::where('release_id',$release->id)->get();

        $tickets = [];
        $projects = [];

        foreach($release_tickets as $ticket){            

            if(!array_key_exists($ticket->ticket->project_id,$projects)){

                $projects[$ticket->ticket->project_id]['project'] = $ticket->ticket->project->name;
            }

            $projects[$ticket->ticket->project_id]['tickets'][$ticket->ticket->type->name][] = $ticket->ticket;

        }
  
        $types = Type::pluck('icon','name');

        return View('release/show',compact('release','projects','types','release_tickets'));        
    }

    public function store(Request $request)
    {
        $release = new Release();

        $release->title = $request->title;
        $release->started_at = new Carbon($request->started_at);
        $release->completed_at = new Carbon($request->completed_at);
        $release->body = $request->body;
        $release->user_id = Auth::id();

        $release->save();

        \Session::flash('info_message', 'Release Saved');

        return redirect('release/' . $release->id);        

    }
}
