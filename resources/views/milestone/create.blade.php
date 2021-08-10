@extends('layouts.app')
@section('title')
Create Milestone
@stop
<!-- Main Content -->
@section('content')
<h1>Create New Milestone</h1>
<hr>
{!! Form::open(['method' => 'POST', 'url' => 'milestone/store/new', 'class' => 'form','id'=>'milestone_form']) !!}
<div class="form-group">
    {!! Form::label('start_at', 'Start Date') !!}
    {!! Form::text('start_at', date('m/d/Y'), ['class' => 'form-control required','id'=>'start_at']) !!}
</div>
<div class="form-group">
    {!! Form::label('due_at', 'Due Date') !!}
    {!! Form::text('due_at', date('m/d/Y',strtotime('+2 weeks')), ['class' => 'form-control','id'=>'due_at']) !!}
</div>
<div class="form-group">
    {!! Form::label('name', 'Milestone Name') !!}
    {!! Form::text('name', null, ['class' => 'form-control required']) !!}
</div>
<div class="form-group">
    {!! Form::label('description', 'Milestone Description') !!}
    {!! Form::textarea('description', null, ['class' => 'form-control summernote']) !!}
</div>
<div class="form-group">
      {!! Form::label('owner_user_id', 'Product Owner') !!}
      {!! Form::select('owner_user_id', $users, '', ['class' => 'form-control', 'required' => 'required']) !!}
  </div>

  <div class="form-group">
      {!! Form::label('scrummaster_user_id', 'Scrum Master / Sprint Manager') !!}
      {!! Form::select('scrummaster_user_id', $users, '', ['class' => 'form-control', 'required' => 'required']) !!}
  </div>  
<div class="form-group">
{!! Form::submit('Save Milestone', ['class' => 'btn btn-success pull-right']) !!}
</div>
{!! Form::close() !!}
@endsection
@section('javascript')
  <script src="/js/summernote.min.js"></script>
  <script>
      $(function() {

          $("#milestone_form").validate();

        $('.summernote').summernote({
    height: 300
              });

$("#due_at,#start_at").datepicker();

            });
</script>
@endsection
