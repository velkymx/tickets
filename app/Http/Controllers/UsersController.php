<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Illuminate\Support\Facades\Auth;

use App\Ticket;

use App\User;

class UsersController extends Controller
{

    public function show($id)
    {

      $user = User::findOrFail($id);

      $statuses = \App\Status::lists('name','id');



      foreach($statuses as $status => $val){

          $alltickets[$val] = Ticket::where('user_id2',$id)->where('status_id',$status)->get();

          if(sizeof($alltickets[$val])==0) unset($alltickets[$val]);

      }

      return View('users.show',compact('user','alltickets'));

    }

}
