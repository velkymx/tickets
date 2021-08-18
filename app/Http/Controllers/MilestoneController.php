<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Str;

class MilestoneController extends Controller
{

  public function __construct()
  {
      $this->middleware('auth');
  }


    public function index()
    {

      $milestones = \App\Milestone::all();

      return view('milestone.index',compact('milestones'));

    }

    public function print(Request $request)
    {

      $milestone = \App\Milestone::findOrFail($request->id);

      $projects = [];

      foreach($milestone->tickets as $tic){
        $projects[$tic->project->id] = $tic->project->name;
      }

      $types = \App\Type::all();

      return view('milestone.print',compact('milestone','types','projects'));

    }    

    public function getShow(Request $request)
    {

      $milestone = \App\Milestone::findOrFail($request->id);

      $tmpcodes = \App\Status::get();

      $statuscodes = [];

      foreach($tmpcodes as $code){

        $statuscodes[$code->id] = [
          'name' => $code->name,
          'slug' => Str::slug($code->name,'_')
        ];

      }

      $completed = 0;

      $completed = $milestone->tickets()->whereIn('status_id',['5','8','9'])->count();

      $total = $milestone->tickets->count();

      $percent = 0;

      if($total !== 0 && $completed !== 0){

        $percent = (round($completed / $total,2)*100);

      }

      if ($completed == $total){

        $percent = 100;

      }

      return view('milestone.show',compact('milestone','statuscodes','completed','percent'));

    }

    public function create()
    {

      $users = \App\User::pluck('name','id');

      return view('milestone.create',compact('users'));
    }

    public function edit(Request $request,$id)
    {
      $milestone = \App\Milestone::findOrFail($request->id);

      $users = \App\User::pluck('name','id');

      return view('milestone.edit',compact('milestone','users'));
    }

    public function store(Request $request)
    {

      $post = $request->toArray();

      foreach(['start_at','due_at','end_at'] as $date){

        if(isset($post[$date]) && $post[$date] <> ''){

          $post[$date] = date('Y-m-d',strtotime($post[$date]));

        } else {

          $post[$date] = null;
        }
      }

      if($request->id == 'new'){

        $post['active'] = 1;

        \App\Milestone::create($post);

      } else {

        $milestone = \App\Milestone::findOrFail($request->id);

        $milestone->update($post);

      }

      return redirect('milestone');
    }
}
