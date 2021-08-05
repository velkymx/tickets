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
      {!! Form::textarea('description', null, ['class' => 'form-control summernote']) !!}
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
  <div class="form-group">
    {!! Form::label('estimate', 'Time Estimate (hours)') !!}
    {!! Form::text('estimate', 0, ['class' => 'form-control']) !!}
</div>
  {!! Form::submit('Save Ticket', ['class' => 'btn btn-info pull-right']) !!}
{!! Form::close() !!}
<br /><br /><br /><br />
@stop
@section('javascript')
<script>
    $(function() {
      $( ".datepicker" ).datepicker();
    })
</script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.8.2/tinymce.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.8.2/icons/default/icons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.8.2/plugins/table/plugin.min.js"></script>
<script>
tinymce.init({
    selector: '.summernote',
    plugins: ' preview paste searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern noneditable help charmap quickbars emoticons',     
    toolbar: 'fontsizeselect formatselect | bold italic underline strikethrough | forecolor backcolor removeformat | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist |  image media link', 
    toolbar_sticky: true,
    height : 500,
    menubar: false,  
});
</script> 
@stop
