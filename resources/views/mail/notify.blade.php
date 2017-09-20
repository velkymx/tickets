<p>{{$ticket->user->name}} updated Ticket #{{$ticket->id}} - {{$ticket->subject}}</p>
<hr>
<p>{!! $ticket->notes()->where('ticket_id',$ticket->id)->orderBy('created_at','desc')->first()->body !!}</p>
<hr>
<p><strong>Importance</strong> {{$ticket->importance->name}}</p>
<p><strong>Status</strong> {{$ticket->status->name}}</p>
<p><strong>Assignee</strong> {{$ticket->assignee->name}}</p>
<p><strong>Type</strong> {{$ticket->type->name}}</p>
<hr>
Notification generated using Tickets! https://github.com/velkymx/tickets
