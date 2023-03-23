<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Models\Ticket;
use App\Models\TicketEstimate;
use App\Models\TicketUserWatcher;
use App\Models\Note;
use App\Models\Status;
use App\Models\TicketView;
use App\Models\ReleaseTicket;

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

    public function home()
    {        

        $statuses = Status::whereNotIn('id', [5,8,9])->pluck('name','id');
  
        foreach($statuses as $status => $val){
  
            $alltickets[$val] = Ticket::where('user_id2',Auth::id())->where('status_id',$status)->get();
  
            if(sizeof($alltickets[$val])==0) unset($alltickets[$val]);
  
        }
  
        return View('home',compact('alltickets'));
    }

    public function index(Request $request)
    {
        $perpage = 10;


        if($request->has('perpage')){
            $perpage = (int) $request->perpage;
        }

        $filters = array('milestone_id', 'project_id', 'sprint_id', 'status_id', 'type_id', 'user_id', 'importance_id','q');

        $tickets = new Ticket;

        // this search filter needs to be reworked

        $queryfilter = array();

        foreach($filters as $filter){

            $queryfilter[$filter] = $request->$filter;

            if($request->has($filter) && is_numeric($request->$filter)){
                
                $tickets = $tickets->where($filter, $request->$filter);
            }

            if($filter == 'q'){
                $tickets = $tickets->where('subject', 'like', '%'.$request->$filter.'%');
            }   
            
            if($filter == 'status_id' && $request->status_id == 'none'){

                $tickets = $tickets->whereNotIn('status_id', [5,8,9]);
    
            }
        }

        $tickets = $tickets->orderBy('importance_id','DESC')->paginate($perpage);

        $lookups = $this->lookups();

        $lookups['types'][0] = 'No Change';
        $lookups['milestones'][0] = 'No Change';
        $lookups['importances'][0] = 'No Change';
        $lookups['projects'][0] = 'No Change';
        $lookups['statuses'][0] = 'No Change';
        $lookups['users'][0] = 'No Change';
        $lookups['releases'][0] = 'No Change';

        $viewfilters = $this->lookups();

        $viewfilters['statuses']['none'] = 'Any Active Status';
        $viewfilters['statuses']['all'] = 'Any Status';
        $viewfilters['types']['none'] = 'Any Type';
        $viewfilters['milestones']['none'] = 'Any Milestone'; 
        

        $filter = [
            'milestone_id' => 'none',
            'type_id' => 'none',
            'status_id' => 'none',
        ]; 
        
        foreach($filter as $fk => $fv){

            if($request->has($fk)){
                
                $filter[$fk] = $request->$fk;
            }
    

        }

        return view('tickets.list', compact('tickets', 'queryfilter', 'lookups','viewfilters','filter'));
    }

    public function claim($id)
    {
        $ticket = Ticket::findOrFail($id);

        $request = $ticket->toArray();

        $request['user_id2'] = Auth::id();

        $change_list = $this->changes($ticket->toArray(), $request);

        $ticket->update($request);

        $this->notate($ticket->id, '', $change_list);

        \Session::flash('info_message', 'You are assigned to Ticket #' . $id);

        return redirect('tickets/' . $id);        
    }

    public function show($id)
    {
        $ticket = Ticket::findOrFail($id);

        $lookups = $this->lookups();

        TicketView::create(['user_id' => Auth::id(), 'ticket_id' => $ticket->id]);

        return view('tickets.show', compact('ticket', 'lookups'));
    }

    public function create($value = '')
    {
        $lookups = $this->lookups();

        return view('tickets.create', compact('lookups'));
    }

    public function clone($id)
    {
        $ticket = Ticket::findOrFail($id);

        $lookups = $this->lookups();

        if($ticket->closed_at <> ''){
            $ticket->closed_at = date('m/d/Y',strtotime($ticket->closed_at));
        }

        if($ticket->due_at <> ''){
            $ticket->due_at = date('m/d/Y',strtotime($ticket->due_at));
        }        

        return view('tickets.clone', compact('ticket', 'lookups'));
    }    

    public function edit($id)
    {
        $ticket = Ticket::findOrFail($id);

        $lookups = $this->lookups();

        if($ticket->closed_at <> ''){
            $ticket->closed_at = date('m/d/Y',strtotime($ticket->closed_at));
        }

        if($ticket->due_at <> ''){
            $ticket->due_at = date('m/d/Y',strtotime($ticket->due_at));
        }        

        return view('tickets.edit', compact('ticket', 'lookups'));
    }

    public function update(Request $request, $id)
    {

        $ticket = Ticket::findOrFail($id);

        $request = $request->toArray();

        if (isset($request['due_at']) && $request['due_at'] <> '') {
            $request['due_at'] = date('Y-m-d', strtotime($request['due_at']));
        }

        if (isset($request['closed_at']) && $request['closed_at'] <> '') {
            $request['closed_at'] = date('Y-m-d H:i:s', strtotime($request['closed_at']));
        }    

        $change_list = $this->changes($ticket->toArray(), $request);

        $ticket->update($request);

        $this->notate($ticket->id, '', $change_list);

        \Session::flash('info_message', 'Ticket #' . $id . ' updated');

        return redirect('tickets/' . $id);
    }

    public function store(Request $request)
    {
        $data = $request->toArray();

        $data['user_id'] = Auth::id();

        if (isset($data['due_at']) && $data['due_at'] <> '') {
            $data['due_at'] = date('Y-m-d', strtotime($data['due_at']));
        }

        $insert = Ticket::create($data);

        $request->session()->flash('status', 'Task was created successfully!');

        return redirect('tickets/'.$insert->id);
    }

    public function upload(Request $request)
    {

        // return $request->input('folder');

        if (isset($_FILES) && sizeof($_FILES) > 0) {
            $path = '/images/' . $request->input('folder') . '/';

            if (!is_dir($_SERVER['DOCUMENT_ROOT'].$path)) {
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

            if($request->has('release_id') && $request->release_id >0){
                $release_ticket = new ReleaseTicket();
                $release_ticket->release_id = $request->release_id;
                $release_ticket->ticket_id = $ticket;
                $release_ticket->save();
            }

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

            $ticket = Ticket::findOrFail($request->ticket_id);

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

            $ticket->actual = Note::where('ticket_id',$ticket->id)->sum('hours');          

            $ticket->save();
        }

        return redirect('tickets/' . $request['ticket_id']);
    }

    private function lookups()
    {
        return array(

            'types' => \App\Models\Type::orderBy('name')->pluck('name', 'id'),
            'milestones' => \App\Models\Milestone::orderBy('name')->where('end_at', null)->pluck('name', 'id'),
            'importances' => \App\Models\Importance::orderBy('name')->pluck('name', 'id'),
            'projects' => \App\Models\Project::orderBy('name')->where('active', 1)->pluck('name', 'id'),
            'statuses' => \App\Models\Status::orderBy('name')->pluck('name', 'id'),
            'releases' => \App\Models\Release::orderBy('title')->pluck('title', 'id'),
            'users' => \App\Models\User::orderBy('name')->pluck('name', 'id')

        );
    }

    public function estimate(Request $request, $ticket_id)
    {

        $check = \App\Models\TicketEstimate::where('ticket_id', $ticket_id)->where('user_id', Auth::id())->first();

        if ($check === null) {
            \App\Models\TicketEstimate::create([
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

        $getAvg = TicketEstimate::where('ticket_id', $ticket_id)->get();

        $total = 0;

        foreach ($getAvg as $row) {

            $total += $row->storypoints;
        }

        // 0 - No Effort
        // 1 - XS (Extra Small), Dachshund, Kid Hot Chocolate, One
        // 2 - Somewhere between XS and S
        // 3 - S (Small), Terrier, Tall Late, Cookie
        // 5 - M (Medium), Labrador, Grande Mocha, Cheeseburger
        // 8 - L (Large), Saint Bernard, Vente Iced Late, Cheeseburge with Fries and Soda
        // 13 - Somewhere between L and XL
        // 21 - XL (Extra Large), Great Dane, Trenta Mocha Frap, 5 Course Meal    
        
        $avg = $total / sizeof($getAvg);

        $fibs = [1,2,3,5,8,13,21];

        foreach($fibs as $fkey => $fib){

            if($avg == $fib){
                $sp = $fib;
            }

            if($avg > $fib){
                $sp = $fibs[$fkey+1];
            }            
        }

        $old = $ticket = \App\Models\Ticket::find($ticket_id);

        $ticket->storypoints = $sp;

        $ticket->save();

        $change_list = $this->changes($old->toArray(), $ticket->toArray());

        $this->notate($ticket->id, '', ['Story Points changed to ' . $request->storypoints]);

        return redirect('tickets/' . $ticket_id);
    }

    private function changes($old, $new)
    {

        $changes = ['subject', 'description', 'type_id', 'status_id', 'importance_id', 'milestone_id', 'project_id', 'estimate','user_id2','storypoints'];

        $lookups = $this->lookups();

        $change_list = [];

        foreach ($changes as $change) {

            if ($old[$change] != $new[$change]) {

                $label = $change;

                if (substr($change, -3, 3) == '_id' || substr($change, -3, 3) == 'id2') {

                    $label = substr($change, 0, strlen($change) - 3);

                    $lookup = $label . 's';

                    if ($change == 'status_id') {
                        $lookup = 'statuses';
                    }

                    if($change == 'storypoints'){
                        $label = 'Story points';
                    }

                    if ($change == 'user_id2') {
                        $lookup = 'users';
                        $label = 'Assigned user';

                        // set a watcher

                        $watch = TicketUserWatcher::where('ticket_id',$old['id'])->where('user_id',$new[$change])->first();

                        if(!$watch){
                            TicketUserWatcher::create(['user_id'=>$new[$change],'ticket_id'=>$old['id']]);
                        }

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

            Note::create($insert);
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

            Note::create($insert);
        }

        if ($ticket->watchers->count() > 0) {
            foreach ($ticket->watchers as $watcher) {
                if($watcher->user_id == Auth::id()){
                    continue;
                }
                
                Mail::to($watcher->user->email)->send(new \App\Mail\NotifyWatchers($ticket));
            }
        }
    }

    public function fetch(Request $request)
    {

        return TicketResource::collection(Ticket::whereBetween('closed_at', [$request->started_at, $request->completed_at])->get());
    }
}
