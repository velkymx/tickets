<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Illuminate\Support\Facades\Auth;

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

    public function getShow(Request $request)
    {

      $milestone = \App\Milestone::findOrFail($request->id);

      $statuscodes = \App\Status::get();

      $completed = $milestone->tickets()->whereNotIn('status_id',['5','8','9'])->count();

      $total = $milestone->tickets->count();

      if($total == 0){

        $completed = 0;

      }

      if($total !== 0 && $completed !== 0){

        $percent = 100-(round($completed / $total,2)*100);

      }

      return view('milestone.show',compact('milestone','statuscodes','completed','percent'));

    }

    public function create()
    {
      return view('milestone.create');
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

      return redirect('milestones');
    }
}
