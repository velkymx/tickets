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

      $statuses = \App\Status::pluck('name','id');      

      foreach($statuses as $status => $val){

          $alltickets[$val] = Ticket::where('user_id2',$id)->where('status_id',$status)->get();

          if(sizeof($alltickets[$val])==0) unset($alltickets[$val]);

      }

      $time = new \DateTime(NULL, new \DateTimeZone($user->timezone));
    
      // Us dumb Americans can't handle millitary time
      $ampm = $time->format('H') > 12 ? ' ('. $time->format('g:i a'). ')' : '';
  
      // Remove region name and add a sample time
      $currenttime = $time->format('H:i') . $ampm;      

      return View('users.show',compact('user','alltickets','currenttime'));

    }

    public function edit()
    {
      
      $user = User::findOrFail(Auth::id());

      $timezones = $this->get_timezones();

      $themes = [
        '/css/bootstrap.min.css' => 'Default',
        '/css/bootstrap.darkly.min.css' => 'Darkly'
      ];

      return View('users.edit',compact('user','timezones','themes'));
      
    }

    public function update(Request $request)
    {
      $user = User::findOrFail(Auth::id());
      $user->name = $request->name;
      $user->email = $request->email;
      $user->phone = $request->phone;
      $user->timezone = $request->timezone;
      $user->theme = $request->theme;
      $user->title = $request->title;
      $user->bio = $request->bio;

      $user->save();

      \Session::flash('info_message', 'Profile Changes Saved');

      return redirect('users/' . Auth::id());

    }    

    public function watch($id)
    {

      $ticket = Ticket::findOrFail($id);

      $watch = \App\TicketUserWatcher::where('ticket_id',$id)->where('user_id',Auth::id())->first();

      if($watch){

        $watch->delete();

        $message = 'Watch stopped for this ticket';

      } else {

        \App\TicketUserWatcher::create(['user_id'=>Auth::id(),'ticket_id'=>$id]);

        $message = 'Watch started for this ticket';

      }

      return $message;

    }

    private function get_timezones()
    {
      $regions = array(
        'Africa' => \DateTimeZone::AFRICA,
        'America' => \DateTimeZone::AMERICA,
        'Antarctica' => \DateTimeZone::ANTARCTICA,
        'Aisa' => \DateTimeZone::ASIA,
        'Atlantic' => \DateTimeZone::ATLANTIC,
        'Europe' => \DateTimeZone::EUROPE,
        'Indian' => \DateTimeZone::INDIAN,
        'Pacific' => \DateTimeZone::PACIFIC
    );
    
    $timezones = array();
    foreach ($regions as $name => $mask)
    {
        $zones = \DateTimeZone::listIdentifiers($mask);
        foreach($zones as $timezone)
        {
        // Lets sample the time there right now
        $time = new \DateTime(NULL, new \DateTimeZone($timezone));
    
        // Us dumb Americans can't handle millitary time
        $ampm = $time->format('H') > 12 ? ' ('. $time->format('g:i a'). ')' : '';
    
        // Remove region name and add a sample time
        $timezones[$name][$timezone] = substr($timezone, strlen($name) + 1) . ' - ' . $time->format('H:i') . $ampm;
      }
    }     
    
    return $timezones;
    }

}
