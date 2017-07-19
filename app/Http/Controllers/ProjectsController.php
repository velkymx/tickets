<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class ProjectsController extends Controller
{
    public function index()
    {

      $projects = \App\Project::get();

      return view('projects.index',compact('projects'));

    }

    public function show($id)
    {

      $project = \App\Project::findOrFail($id);

      $perpage = 10;

      $filters = array('milestone_id','project_id','sprint_id','status_id','type_id','user_id','importance_id');

      $queryfilter = array();

      foreach($filters as $filter){

        if(isset($request->$filter) && is_numeric($request->$filter)){

          $queryfilter[$filter] = $request->$filter;

        }

      }

      if(is_array($queryfilter) && sizeof($queryfilter)>0){

          $tickets = new \App\Ticket;

          foreach ($queryfilter as $filter => $value) {

            $tickets = $tickets->where($filter,$value);

          }

          $tickets = $tickets-->paginate($perpage);

      } else {

        $tickets = \App\Ticket::where('project_id',$project->id)->paginate($perpage);

      }

      return view('projects.show',compact('project','tickets','queryfilter'));
    }

    public function create()
    {
      # code...
    }

    public function edit(Request $request)
    {
      # code...
    }

    public function store(Request $request)
    {
      # code...
    }
}
