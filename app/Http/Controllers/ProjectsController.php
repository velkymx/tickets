<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\Status;

class ProjectsController extends Controller
{

  public function __construct()
  {
      $this->middleware('auth');
  }
    
    public function index()
    {
        $projects = Project::orderBy('name')->get();

        return view('projects.index', compact('projects'));
    }

    public function show($id)
    {
        $project = Project::findOrFail($id);

        $perpage = 10;

        $filters = array('milestone_id','project_id','sprint_id','status_id','type_id','user_id','importance_id');

        $queryfilter = array();

        foreach ($filters as $filter) {
            if (isset($request->$filter) && is_numeric($request->$filter)) {
                $queryfilter[$filter] = $request->$filter;
            }
        }

        if (is_array($queryfilter) && sizeof($queryfilter)>0) {
            $tickets = new Ticket;

            foreach ($queryfilter as $filter => $value) {
                $tickets = $tickets->where($filter, $value);
            }

            $tickets = $tickets->paginate($perpage);
        } else {
            $tickets = Ticket::where('project_id', $project->id)->paginate($perpage);
        }

        $statuscodes = Status::get();

        $percent = 0;

        $completed = $project->tickets()->whereNotIn('status_id', ['5','8','9'])->count();

        $total = $project->tickets->count();

        if ($total !== 0 && $completed !== 0) {
            $percent = 100-(round($completed / $total, 2)*100);
        }

        return view('projects.show', compact('project', 'tickets', 'queryfilter', 'total', 'completed', 'percent', 'statuscodes'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function edit(Request $request)
    {
        $project = Project::findOrFail($request->id);

        return view('projects.edit', compact('project'));
    }

    public function store(Request $request)
    {
        if ($request->id == 'new') {
            $post = $request->toArray();

            $post['active'] = 1;

            Project::create($post);
        } else {
            $project = Project::findOrFail($request->id);

            $project->update($request->toArray());
        }



        return redirect('projects');
    }
}
