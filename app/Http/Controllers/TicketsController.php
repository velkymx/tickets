<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Ticket;

use Illuminate\Support\Facades\Auth;

class TicketsController extends Controller
{

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware('auth');
  }

    public function index(Request $request){

      $perpage = 10;

      $filters = array('milestone_id','project_id','sprint_id','status_id','type_id','user_id','importance_id');

      $queryfilter = array();

      foreach($filters as $filter){

        if(isset($request->$filter) && is_numeric($request->$filter)){

          $queryfilter[$filter] = $request->$filter;

        }

      }

      if(is_array($queryfilter) && sizeof($queryfilter)>0){

          $tickets = new Ticket;

          foreach ($queryfilter as $filter => $value) {

            $tickets = $tickets->where($filter,$value);

          }

          $tickets = $tickets->paginate($perpage);

      } else {

        $tickets = Ticket::paginate($perpage);

      }

      $lookups = $this->lookups();

      $lookups['types'][0] = 'No Change';
      $lookups['milestones'][0] = 'No Change';
      $lookups['importances'][0] = 'No Change';
      $lookups['projects'][0] = 'No Change';
      $lookups['statuses'][0] = 'No Change';
      $lookups['users'][0] = 'No Change';

      return view('tickets.list',compact('tickets','queryfilter','lookups'));

    }

    public function show($id){

        $ticket = Ticket::findOrFail($id);

        $lookups = $this->lookups();

        return view('tickets.show',compact('ticket','lookups'));

    }

    public function create($value='')
    {

      $lookups = $this->lookups();

      return view('tickets.create',compact('lookups'));

    }

    public function edit($id)
    {
      $ticket = Ticket::findOrFail($id);

      $lookups = $this->lookups();

      return view('tickets.edit',compact('ticket','lookups'));
    }

    public function update(Request $request,$id)
    {

      // $request = Request::all();

      $ticket = Ticket::findOrFail($id);

      if(isset($request['due_at']) && $request['due_at'] <> ''){

        $request['due_at'] = date('Y-m-d',strtotime($request['due_at']));

      }

      $ticket->update($request->toArray());

      \Session::flash('info_message','Ticket #'.$id.' updated');

      return redirect('tickets/'.$id);

    }

    public function store(Request $request)
    {

      $request = $request->toArray();

      $request['user_id'] = Auth::id();

      if(isset($request['due_at']) && $request['due_at'] <> ''){

        $request['due_at'] = date('Y-m-d',strtotime($request['due_at']));

      }

      Ticket::create($request);

      return redirect('tickets');

    }

    public function upload(Request $request)
    {

      // return $request->input('folder');

      if(isset($_FILES) && sizeof($_FILES) > 0){

        $path = '/images/'.$request->input('folder').'/';

        if(!is_dir($path)){

            mkdir($_SERVER['DOCUMENT_ROOT'].$path);

        }

        move_uploaded_file($_FILES['file']['tmp_name'],$_SERVER['DOCUMENT_ROOT'].$path.$_FILES['file']['name']);

        return $path.$_FILES['file']['name'];

      }

    }

    public function batch(Request $request)
    {
      $post = $request->toArray();

      $tickets = $post['tickets'];

      unset($post['tickets']);

      if(count($tickets) == 0){

        return redirect('tickets');

      }

      foreach($post as $k => $v){

        if($v == 0){

          unset($post[$k]);

        }

      }

      $i = 0;

      foreach($tickets as $ticket){

        $update = Ticket::findOrFail($ticket);

        $update->update($post);

        $i++;

      }

      \Session::flash('info_message', $i.' ticket(s) updated');

      return redirect('tickets');

    }

    private function lookups()
    {

      return array(

        'types' => \App\Type::pluck('name','id'),
        'milestones' => \App\Milestone::where('end_at',null)->pluck('name','id'),
        'importances' => \App\Importance::pluck('name','id'),
        'projects' => \App\Project::where('active',1)->pluck('name','id'),
        'statuses' => \App\Status::pluck('name','id'),
        'users' => \App\User::pluck('name','id')

      );

    }
}
