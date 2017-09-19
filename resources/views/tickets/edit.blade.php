@extends('layouts.app')
@section('title')
Create Ticket
@stop
@section('content')
<h1>Create a new Ticket</h1>
<hr />
{!! Form::open(['url'=>'tickets/update/'.$ticket->id]) !!}
<div class="form-group">
  {!!Form::text('subject',$ticket->subject,['placeholder'=>'Ticket Subject','class'=>'form-control', 'required' => 'required'])!!}
</div>
  <div class="form-group">
      {!! Form::label('description', 'Ticket Details') !!}
      {!! Form::textarea('description', $ticket->description, ['class' => 'form-control summernote', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('type_id', 'Ticket Type') !!}
      {!! Form::select('type_id', $lookups['types'], $ticket->types, ['class' => 'form-control', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('importance_id', 'Ticket Importance') !!}
      {!! Form::select('importance_id', $lookups['importances'], $ticket->importances, ['class' => 'form-control', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('milestone_id', 'Ticket Milestone') !!}
      {!! Form::select('milestone_id', $lookups['milestones'], $ticket->milestones, ['class' => 'form-control', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('status_id', 'Ticket Status') !!}
      {!! Form::select('status_id', $lookups['statuses'], $ticket->statuses, ['class' => 'form-control', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('project_id', 'Ticket Project') !!}
      {!! Form::select('project_id', $lookups['projects'], $ticket->projects, ['class' => 'form-control', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('user_id2', 'Assign To') !!}
      {!! Form::select('user_id2', $lookups['users'], $ticket->users, ['class' => 'form-control', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('due_at', 'Due Date') !!}
      {!! Form::text('due_at', date('m/d/Y',strtotime($ticket->due_at)), ['class' => 'form-control datepicker']) !!}
  </div>
  <div class="form-group">
    {!! Form::label('estimate', 'Time Estimate (hours)') !!}
    {!! Form::text('estimate', $ticket->estimate, ['class' => 'form-control']) !!}
</div>

  {!! Form::submit('Save Ticket', ['class' => 'btn btn-info pull-right']) !!}
{!! Form::close() !!}
<br /><br /><br /><br />
@stop
@section('javascript')
<script src="/js/summernote.min.js"></script>
<script>
    $(function() {
      $( ".datepicker" ).datepicker();
      $('.summernote').summernote({
  height: 300,
  onImageUpload: function(files, editor) {
                  sendFile(files[0],'.summernote');
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
         url: "/tickets/upload",
         cache: false,
         contentType: false,
         processData: false,
         success: function(url) {
             $(editor).summernote('editor.insertImage', url);
         }
     });
 }
</script>
@stop
