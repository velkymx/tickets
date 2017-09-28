@extends('layouts.app')
@section('title')
Ticket #{{$ticket->id}}
@stop
<!-- Main Content -->
@section('content')

  <ol class="breadcrumb">
    <li><a href="/">Home</a></li>
    <li><a href="/tickets/">Tickets</a></li>
    <li class="active">Ticket #{{$ticket->id}}</li>
  </ol>
  <div class="row-fluid">
    <div class="col-md-8">
<h2>{{$ticket->subject}}</h2>
<hr />
{!!html_entity_decode($ticket->description)!!}
<hr />
<div class="alert alert-info alert-dismissible" role="alert" id="alert" style="display:none">
  <button type="button" class="close" onclick="$('#alert').hide()" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <div id="alert_messsage"></div>
</div>
<h3>Notes ({{$ticket->notes()->where('hide','0')->count()}})

<span class="pull-right"><span class="btn btn-info btn-sm" id="watch"><i class="glyphicon glyphicon-eye-open"></i> Watch</span></span>

</h3>
<hr />
@foreach ($ticket->notes()->where('hide','0')->get() as $note)
<div class="panel panel-default" id="note_{{$note->id}}">
  <div class="panel-heading">
<strong><i class="glyphicon glyphicon-user"></i> {{$note->user->name}}</strong> | posted {{date('M jS, Y g:ia',strtotime($note->created_at))}}
<span class="pull-right"><button onclick="hideNote('{{$note->id}}');" class="btn btn-default btn-xs">Remove</button></span>
</div>
<div class="panel-body">
{!!html_entity_decode($note->body)!!}
</div>
</div>
@endforeach
<hr />
{!! Form::open(['url'=>'notes']) !!}

<div class="form-group">
    {!! Form::label('body', 'Status Update and Notes') !!}
    {!! Form::textarea('body', null, ['class' => 'form-control summernote', 'required' => 'required']) !!}
</div>
<div class="form-group">
    {!! Form::label('status_id', 'Change Status') !!}
    {!! Form::select('status_id', $lookups['statuses'], $ticket->status->id, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
<div class="form-group">
    {!! Form::label('importance_id', 'Comment Importance') !!}
    {!! Form::select('importance_id',$lookups['importances'], $ticket->importance->id, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
<div class="form-group">
    {!! Form::label('hours', 'Add Time or Quantity') !!}
    {!! Form::text('hours', 0, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
{!! Form::hidden('ticket_id',$ticket->id) !!}
{!! Form::submit('Save Comment', ['class' => 'btn btn-success']) !!}
{!! Form::close() !!}
</div>
<div class="col-md-4">
<a href="/tickets/edit/{{$ticket->id}}" class="btn btn-default pull-right">Edit</a>
<br /><br />
<div class="panel panel-default">
  <div class="panel-heading">
  Details
</div>
  <div class="panel-body">
    <table class="table">
      @if ($ticket->due_at > 0)
        <tr>
          <td>Due</td>
          <td>{{date('M jS, Y',strtotime($ticket->due_at))}}</td>
        </tr>
      @endif
      <tr>
        <td>Importance</td>
        <td>{{$ticket->importance->name}}</td>
      </tr>
      <tr>
        <td>Status</td>
        <td>{{$ticket->status->name}}</td>
      </tr>
      <tr>
        <td>Assignee</td>
        <td><a href="/users/{{$ticket->assignee->id}}">{{$ticket->assignee->name}}</a></td>
      </tr>
      <tr>
        <td>Type</td>
        <td>{{$ticket->type->name}}</td>
      </tr>
      <tr>
        <td>Owner</td>
        <td><a href="/users/{{$ticket->user->id}}">{{$ticket->user->name}}</a></td>
      </tr>
      <tr>
        <td>Milestone</td>
        <td><a href="/milestone/show/{{$ticket->milestone->id}}">{{$ticket->milestone->name}}</a></td>
      </tr>
      <tr>
        <td>Project</td>
        <td><a href="/projects/show/{{$ticket->project->id}}">{{$ticket->project->name}}</a></td>
      </tr>
      <tr>
        <td>Time Estimate</td>
        <td>{{$ticket->estimate}} hours</td>
      </tr>
      <tr>
        <td>Time Actual</td>
        <td>{{$ticket->notes()->where('hide',0)->sum('hours')}} hours</td>
      </tr>
      <tr>
        <td>Created</td>
        <td>{{date('M jS, Y g:ia',strtotime($ticket->created_at))}}</td>
      </tr>
      <tr>
        <td>Updated</td>
        <td>{{date('M jS, Y g:ia',strtotime($ticket->updated_at))}}</td>
      </tr>
      @foreach ($ticket->watchers as $watcher)
        <tr>
          <td>Watcher</td>
          <td><a href="mailto:{{$watcher->user->email}}?subject=Ticket #{{$ticket->id}}">{{$watcher->user->name}}</a></td>
        </tr>
      @endforeach
      @foreach ($ticket->views()->select([DB::raw('DISTINCT user_id'),DB::raw('max(created_at) as viewed_at')])->groupBy('user_id')->get() as $view)
        <tr>
          <td>User View</td>
          <td>{{$view->user->name}} - {{\Carbon\Carbon::createFromTimeStamp(strtotime($view->viewed_at))->diffForHumans()}}</td>
        </tr>

      @endforeach
    </table>
  </div>
</div>
</div>
</div>

<span id="ticket_id" style="display:none">{{$ticket->id}}</span>
@stop
@section('javascript')
<script src="/js/summernote.min.js"></script>
<script>
    $(function() {
      $( ".datepicker" ).datepicker();
      $('.summernote').summernote({
  height: 200,
  toolbar: [

  ['style', ['fontsize','bold', 'italic', 'underline', 'clear']],
  ['color', ['color']],
  ['para', ['ul', 'ol', 'paragraph','hr','link','picture']]
  ],
  onImageUpload: function(files, editor) {
                  sendFile(files[0],'.summernote');
              }
            });


          $('.summernote2').summernote({
      height: 200,
      toolbar: [

      ['style', ['fontsize','bold', 'italic', 'underline', 'clear']],
      ['color', ['color']],
      ['para', ['ul', 'ol', 'paragraph','hr','link','picture']]
      ],
      onImageUpload: function(files, editor) {
                      sendFile(files[0],'.summernote2');
                  }
                });
              });

function sendFile(file, editor) {
     data = new FormData();
     data.append("file", file);
     data.append("_token",'{{csrf_token()}}');
     $.ajax({
         data: data,
         type: "POST",
         url: "/tickets/upload/?folder=t{{$ticket->id}}",
         cache: false,
         contentType: false,
         processData: false,
         success: function(url) {
             $(editor).summernote('editor.insertImage', url);
         }
     });
 }

function hideNote(noteid) {

  $("#note_"+noteid).load('/notes/hide/'+noteid);

  $("#note_"+noteid).slideUp();

}

$("#watch").click(

  function(){

    $("#alert_messsage").load('/users/watch/'+$("#ticket_id").html())

    $("#alert").slideDown()

  }

)

</script>
@stop
