@extends('layouts.app')
@section('title')
Ticket #{{$ticket->id}}
@stop
<!-- Main Content -->
@section('content')
  <ol class="breadcrumb">
    <li><a href="/">Home</a></li>
    <li><a href="/tickets/">Tickets</a></li>
    <li class="active">Ticket #{{$ticket->id}} <span class="badge"> {{$ticket->status->name}} </span></li>
  </ol>
  <div class="row-fluid">
    <div class="col-md-8">




<h2>{{$ticket->subject}}</h2>
<hr />
{!!html_entity_decode($ticket->description)!!}
<hr />
<h3>Notes ({{$ticket->notes()->where('hide','0')->count()}})</h3>
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
    {!! Form::label('body', 'Post a Note!') !!}
    {!! Form::textarea('body', null, ['class' => 'form-control summernote', 'required' => 'required']) !!}
</div>
{!! Form::hidden('ticket_id',$ticket->id) !!}
{!! Form::submit('Save Note', ['class' => 'btn btn-success pull-right']) !!}
{!! Form::close() !!}
</div>
<div class="col-md-4">
  <div class="pull-right"><a href="/tickets/create/"><i class="glyphicon glyphicon-plus"></i> Create Ticket</a></div>
  <br /><br />
  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#changeStatus">Change Status</button>
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
        <td><a href="/user/{{$ticket->assignee->id}}">{{$ticket->assignee->name}}</a></td>
      </tr>
      <tr>
        <td>Type</td>
        <td>{{$ticket->type->name}}</td>
      </tr>
      <tr>
        <td>Owner</td>
        <td><a href="/user/{{$ticket->user->id}}">{{$ticket->user->name}}</a></td>
      </tr>
      <tr>
        <td>Milestone</td>
        <td><a href="/tickets/?milestone_id={{$ticket->milestone->id}}">{{$ticket->milestone->name}}</a></td>
      </tr>
      <tr>
        <td>Project</td>
        <td><a href="/projects/show/{{$ticket->project->id}}">{{$ticket->project->name}}</a></td>
      </tr>
      <tr>
        <td>Created</td>
        <td>{{date('M jS, Y g:ia',strtotime($ticket->created_at))}}</td>
      </tr>
      <tr>
        <td>Updated</td>
        <td>{{date('M jS, Y g:ia',strtotime($ticket->updated_at))}}</td>
      </tr>


    </table>
  </div>
</div>
</div>
</div>

<!-- Modal -->
<div class="modal fade" id="changeStatus" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      {!! Form::open(['url'=>'notes']) !!}
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Change Status</span></h4>
      </div>
      <div class="modal-body">


        <div class="form-group">
            {!! Form::textarea('body', null, ['class' => 'form-control summernote2', 'required' => 'required']) !!}
        </div>
        {!! Form::hidden('ticket_id',$ticket->id) !!}
        <div class="form-group">
            {!! Form::label('status_id', 'Ticket Status') !!}
            {!! Form::select('status_id', $lookups['statuses'], 1, ['class' => 'form-control', 'required' => 'required']) !!}
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        {!! Form::submit('Make it so!', ['class' => 'btn btn-primary']) !!}
      </div>
      {!! Form::close() !!}
    </div>
  </div>
</div>

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

</script>
@stop
