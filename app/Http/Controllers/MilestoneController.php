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
}
