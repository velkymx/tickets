@extends('layouts.app')
@section('title')
Create Ticket
@stop
@section('content')
<h1>Create a new Ticket</h1>
<hr />
{!! Form::open(['url'=>'tickets']) !!}
<div class="form-group">
  {!!Form::text('subject','',['placeholder'=>'Ticket Subject','class'=>'form-control', 'required' => 'required'])!!}
</div>
  <div class="form-group">
      {!! Form::label('description', 'Ticket Details') !!}
      {!! Form::textarea('description', null, ['class' => 'form-control summernote', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('type_id', 'Ticket Type') !!}
      {!! Form::select('type_id', $lookups['types'], 3, ['class' => 'form-control', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('importance_id', 'Ticket Importance') !!}
      {!! Form::select('importance_id', $lookups['importances'], 2, ['class' => 'form-control', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('milestone_id', 'Ticket Milestone') !!}
      {!! Form::select('milestone_id', $lookups['milestones'], 1, ['class' => 'form-control', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('status_id', 'Ticket Status') !!}
      {!! Form::select('status_id', $lookups['statuses'], 1, ['class' => 'form-control', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('project_id', 'Ticket Project') !!}
      {!! Form::select('project_id', $lookups['projects'], 4, ['class' => 'form-control', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('user_id2', 'Assign To') !!}
      {!! Form::select('user_id2', $lookups['users'], null, ['class' => 'form-control', 'required' => 'required']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('due_at', 'Due Date') !!}
      {!! Form::text('due_at', null, ['class' => 'form-control datepicker']) !!}
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
