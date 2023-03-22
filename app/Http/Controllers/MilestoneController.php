<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Milestone;
use App\Models\Type;
use App\Models\Status;
use App\Models\User;

class MilestoneController extends Controller
{

  public function __construct()
  {
      $this->middleware('auth');
  }


    public function index()
    {

      $milestones = Milestone::orderBy('name')->get();

      return view('milestone.index',compact('milestones'));

    }

    public function print(Request $request)
    {

      $milestone = Milestone::findOrFail($request->id);

      $projects = [];

      foreach($milestone->tickets as $tic){
        $projects[$tic->project->id] = $tic->project->name;
      }

      $types = Type::all();

      return view('milestone.print',compact('milestone','types','projects'));

    }    

    public function getShow(Request $request)
    {

      $milestone = Milestone::findOrFail($request->id);

      $tmpcodes = Status::get();

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

      $users = User::pluck('name','id');

      return view('milestone.create',compact('users'));
    }

    public function edit(Request $request,$id)
    {
      $milestone = Milestone::findOrFail($request->id);

      $users = User::pluck('name','id');

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

        Milestone::create($post);

      } else {

        $milestone = Milestone::findOrFail($request->id);

        $milestone->update($post);

      }

      return redirect('milestone');
    }
}
