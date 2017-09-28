<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Ticket;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Mail;

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

    public function index(Request $request)
    {
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
            $tickets = Ticket::paginate($perpage);
        }

        $lookups = $this->lookups();

        $lookups['types'][0] = 'No Change';
        $lookups['milestones'][0] = 'No Change';
        $lookups['importances'][0] = 'No Change';
        $lookups['projects'][0] = 'No Change';
        $lookups['statuses'][0] = 'No Change';
        $lookups['users'][0] = 'No Change';

        return view('tickets.list', compact('tickets', 'queryfilter', 'lookups'));
    }

    public function show($id)
    {
        $ticket = Ticket::findOrFail($id);

        $lookups = $this->lookups();

        \App\TicketView::create(['user_id'=>Auth::id(),'ticket_id'=>$ticket->id]);

        return view('tickets.show', compact('ticket', 'lookups'));
    }

    public function create($value='')
    {
        $lookups = $this->lookups();

        return view('tickets.create', compact('lookups'));
    }

    public function edit($id)
    {
        $ticket = Ticket::findOrFail($id);

        $lookups = $this->lookups();

        return view('tickets.edit', compact('ticket', 'lookups'));
    }

    public function update(Request $request, $id)
    {

      // $request = Request::all();

        $ticket = Ticket::findOrFail($id);

        if (isset($request['due_at']) && $request['due_at'] <> '') {
            $request['due_at'] = date('Y-m-d', strtotime($request['due_at']));
        }

        $changes = ['subject', 'description', 'type_id', 'status_id', 'importance_id', 'milestone_id', 'project_id', 'due_at', 'closed_at','estimate'];

        $change_list = [];

        foreach ($changes as $change) {
            if ($request->$change != $ticket->$change) {
                $label = $change;

                if (substr($change, -3, 3) == '_id') {
                    $label = substr($change, 0, strlen($change)-3);

                    $change_list[] = ucwords($label).' changed to '.$ticket->$label->name;
                } elseif (substr($change, -3, 3) == '_at') {
                    $label = substr($change, 0, strlen($change)-3);

                    $change_list[] = ucwords($label).' date changed to '.date('M jS, Y', strtotime($request->$change));
                } else {
                    $change_list[] = ucwords($label).' changed to '.$request->$change;
                }
            }
        }

        $ticket->update($request->toArray());

        $this->notate($ticket->id, '', $change_list);

        \Session::flash('info_message', 'Ticket #'.$id.' updated');

        return redirect('tickets/'.$id);
    }

    public function store(Request $request)
    {
        $request = $request->toArray();

        $request['user_id'] = Auth::id();

        if (isset($request['due_at']) && $request['due_at'] <> '') {
            $request['due_at'] = date('Y-m-d', strtotime($request['due_at']));
        }

        Ticket::create($request);

        return redirect('tickets');
    }

    public function upload(Request $request)
    {

      // return $request->input('folder');

        if (isset($_FILES) && sizeof($_FILES) > 0) {
            $path = '/images/'.$request->input('folder').'/';

            if (!is_dir($path)) {
                mkdir($_SERVER['DOCUMENT_ROOT'].$path);
            }

            move_uploaded_file($_FILES['file']['tmp_name'], $_SERVER['DOCUMENT_ROOT'].$path.$_FILES['file']['name']);

            return $path.$_FILES['file']['name'];
        }
    }

    public function batch(Request $request)
    {
        $post = $request->toArray();

        $tickets = $post['tickets'];

        unset($post['tickets']);

        if (count($tickets) == 0) {
            return redirect('tickets');
        }

        foreach ($post as $k => $v) {
            if ($v == 0) {
                unset($post[$k]);
            }
        }

        $i = 0;

        foreach ($tickets as $ticket) {
            $update = Ticket::findOrFail($ticket);

            $update->update($post);

            $i++;
        }

        \Session::flash('info_message', $i.' ticket(s) updated');

        return redirect('tickets');
    }

    public function board()
    {
        $tickets = Ticket::get();

        $lookups = $this->lookups();

        return view('tickets.board', compact('tickets', 'lookups'));
    }

    public function api(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        if ($request['status'] != $ticket->status_id) {
            $ticket->update(['status_id'=>$request['status']]);

            $this->notate($ticket->id, '', ['Status Changed to '.$ticket->status->name]);

            return 'Success';
        }

        return 'Fail';
    }

    public function note(Request $request)
    {
        $request = $request->toArray();

        $request['user_id'] = Auth::id();

        $changes = [];

        $update = [];

        if (isset($request['status_id']) && isset($request['ticket_id'])) {
            $ticket = \App\Ticket::findOrFail($request['ticket_id']);

            if ($ticket->status_id != $request['status_id']) {
                $changes[] = 'Status changed to '.$ticket->status->name;

                $update[] = ['status_id' => $request['status_id']];
            }

            if ($request['hours'] != 0) {
                $changes[] = 'Hours added: '.$request['hours'];
            }

            // if ($ticket->importance_id != $request['importance_id']) {
            //     $changes[] = 'Importance changed to '.$ticket->importance->name;
            //
            //     $update[] = ['importance_id' => $request['importance_id']];
            // }
        }

        $ticket->update($update);

        $this->notate($ticket->id, $request['body'], $changes);

        return redirect('tickets/'.$request['ticket_id']);
    }

    private function lookups()
    {
        return array(

        'types' => \App\Type::pluck('name', 'id'),
        'milestones' => \App\Milestone::where('end_at', null)->pluck('name', 'id'),
        'importances' => \App\Importance::pluck('name', 'id'),
        'projects' => \App\Project::where('active', 1)->pluck('name', 'id'),
        'statuses' => \App\Status::pluck('name', 'id'),
        'users' => \App\User::pluck('name', 'id')

      );
    }

    private function notate($ticket_id, $message, $changes)
    {
        $insert['user_id'] = Auth::id();
        $insert['ticket_id'] = $ticket_id;
        $insert['body'] = $message;

        $ticket = Ticket::findOrFail($ticket_id);

        $change_list = '';

        if (count($changes) > 0) {
            foreach ($changes as $change) {
                $change_list .= '<li>'.$change.'</li>';
            }

            if ($message <> '') {
                $insert['body'] = $message.'<hr><ul>'.$change_list.'</ul>';
            } else {
                $insert['body'] = '<ul>'.$change_list.'</ul>';
            }
        }

        \App\Note::create($insert);

        if ($ticket->watchers->count() > 0) {
            foreach ($ticket->watchers as $watcher) {
                Mail::to($watcher->user->email)->send(new \App\Mail\NotifyWatchers($ticket));
            }
        }
    }
}
