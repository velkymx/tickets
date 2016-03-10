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

    public function index(){

      $perpage = 10;

      $count = Ticket::count();

      $tickets = Ticket::paginate($perpage);

      return view('tickets.list',compact('tickets','count'));

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

    public function update($id)
    {

      $request = Request::all();

      $ticket = Ticket::findOrFail($id);

      if(isset($request['due_at']) && $request['due_at'] <> ''){

        $request['due_at'] = date('Y-m-d',strtotime($request['due_at']));

      }

      $ticket->update($request);

      return redirect('tickets/?update=success');

    }

    public function store()
    {

      $request = Request::all();

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

    private function lookups()
    {

      return array(

        'types' => \App\Type::lists('name','id'),
        'milestones' => \App\Milestone::lists('name','id'),
        'importances' => \App\Importance::lists('name','id'),
        'projects' => \App\Project::lists('name','id'),
        'statuses' => \App\Status::lists('name','id'),
        'users' => \App\User::lists('name','id')

      );

    }
}
