<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Ticket;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Mail;

use App\Http\Resources\TicketResource;

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

        $filters = array('milestone_id', 'project_id', 'sprint_id', 'status_id', 'type_id', 'user_id', 'importance_id');

        $queryfilter = array();

        foreach ($filters as $filter) {
            if (isset($request->$filter) && is_numeric($request->$filter)) {
                $queryfilter[$filter] = $request->$filter;
            }
        }

        if (is_array($queryfilter) && sizeof($queryfilter) > 0) {
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

        \App\TicketView::create(['user_id' => Auth::id(), 'ticket_id' => $ticket->id]);

        return view('tickets.show', compact('ticket', 'lookups'));
    }

    public function create($value = '')
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

        $ticket = Ticket::findOrFail($id);

        if (isset($request['due_at']) && $request['due_at'] <> '') {
            $request['due_at'] = date('Y-m-d', strtotime($request['due_at']));
        }

        if (isset($request['closed_at']) && $request['closed_at'] <> '') {
            $request['closed_at'] = date('Y-m-d H:i:a', strtotime($request['closed_at']));
        }        

        $change_list = $this->changes($ticket->toArray(), $request);

        $ticket->update($request->toArray());

        $this->notate($ticket->id, '', $change_list);

        \Session::flash('info_message', 'Ticket #' . $id . ' updated');

        return redirect('tickets/' . $id);
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
            $path = '/images/' . $request->input('folder') . '/';

            if (!is_dir($path)) {
                mkdir($_SERVER['DOCUMENT_ROOT'] . $path);
            }

            move_uploaded_file($_FILES['file']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $path . $_FILES['file']['name']);

            return $path . $_FILES['file']['name'];
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

        \Session::flash('info_message', $i . ' ticket(s) updated');

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
            $ticket->update(['status_id' => $request['status']]);

            $this->notate($ticket->id, '', ['Status Changed to ' . $ticket->status->name]);

            return 'Success';
        }

        return 'Fail';
    }

    public function note(Request $request)
    {

        if ($request->has('status_id') && $request->has('ticket_id')) {

            $ticket = \App\Ticket::findOrFail($request->ticket_id);

            $old = $ticket->toArray();

            if ($ticket->status_id != $request->status_id) {

                if ($request->status_id == 5) {
                    $ticket->closed_at = date('Y-m-d H:i:s');
                }

                $ticket->status_id = $request->status_id;
                $ticket->save();
            }

            $change_list = $this->changes($old, $ticket->toArray());

            $this->notate($ticket->id, $request->body, $change_list, $request->hours);

            $ticket->actual = \App\Note::where('ticket_id',$ticket->id)->sum('hours');          

            $ticket->save();
        }

        return redirect('tickets/' . $request['ticket_id']);
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

    public function estimate(Request $request, $ticket_id)
    {

        $check = \App\TicketEstimate::where('ticket_id', $ticket_id)->where('user_id', Auth::id())->first();

        if ($check === null) {
            \App\TicketEstimate::create([
                'ticket_id' => $ticket_id,
                'user_id' => Auth::id(),
                'storypoints' => $request->storypoints
            ]);
        } else {

            if ($check->storypoints == $request->storypoints) {

                return redirect('tickets/' . $ticket_id);
            }

            $check->storypoints = $request->storypoints;
            $check->save();
        }

        $getAvg = \App\TicketEstimate::where('ticket_id', $ticket_id)->where('user_id', Auth::id())->get();

        $total = 0;

        foreach ($getAvg as $row) {

            $total += $row->storypoints;
        }

        $old = $ticket = \App\Ticket::find($ticket_id);

        $ticket->storypoints = $total / sizeof($getAvg);

        $ticket->save();

        $change_list = $this->changes($old->toArray(), $ticket->toArray());

        $this->notate($ticket->id, '', ['Story Points changed to ' . $request->storypoints]);

        return redirect('tickets/' . $ticket_id);
    }

    private function changes($old, $new)
    {

        $changes = ['subject', 'description', 'type_id', 'status_id', 'importance_id', 'milestone_id', 'project_id', 'estimate'];

        $lookups = $this->lookups();

        $change_list = [];

        foreach ($changes as $change) {

            if ($old[$change] != $new[$change]) {

                $label = $change;

                if (substr($change, -3, 3) == '_id') {

                    $label = substr($change, 0, strlen($change) - 3);

                    $lookup = $label . 's';

                    if ($change == 'status_id') {
                        $lookup = 'statuses';
                    }

                    $change_list[] = ucwords($label) . ' changed to ' . $lookups[$lookup][$new[$change]];
                } else {
                    $change_list[] = ucwords($change) . ' changed to ' . $new[$change];
                }
            }
        }

        if (strtotime($old['due_at']) !== strtotime($new['due_at'])) {

            $change_list[] = 'Due date changed to ' . date('M jS, Y', strtotime($new['due_at']));
        }

        if (strtotime($old['closed_at']) !== strtotime($new['closed_at'])) {

            $change_list[] = 'Ticket closed on ' . date('M jS, Y', strtotime($new['closed_at']));
        }

        return $change_list;
    }

    private function notate($ticket_id, $message, $changes, $addhours = 0)
    {

        $insert = [
            'user_id' => Auth::id(),
            'ticket_id' => $ticket_id,
            'body' => $message,
            'hours' => $addhours
        ];

        $ticket = Ticket::findOrFail($ticket_id);

        if (strlen($message) > 0) {

            $insert['notetype'] = 'message';

            \App\Note::create($insert);
        }

        if ($addhours > 0) {
            $changes[] = 'Time or Quantity adjusted by ' . $addhours;
        }

        if (is_array($changes) && count($changes) > 0) {

            $change_list = '';

            foreach ($changes as $change) {
                $change_list .= '<li>' . $change . '</li>';
            }

            $insert['body'] = '<ul>' . $change_list . '</ul>';
            $insert['notetype'] = 'changelog';
            $insert['hours'] = 0;

            \App\Note::create($insert);
        }

        if ($ticket->watchers->count() > 0) {
            foreach ($ticket->watchers as $watcher) {
                Mail::to($watcher->user->email)->send(new \App\Mail\NotifyWatchers($ticket));
            }
        }
    }

    public function fetch(Request $request)
    {

        return TicketResource::collection(Ticket::whereBetween('closed_at', [$request->started_at, $request->completed_at])->get());
    }
}
