@extends('layouts.app')
@section('title')
Create Milestone
@stop
<!-- Main Content -->
@section('content')
<h1>Create New Milestone</h1>
<hr>
{!! Form::open(['method' => 'POST', 'url' => 'milestone/store/'.$milestone->id, 'class' => 'form']) !!}
<div class="form-group">
    {!! Form::label('start_at', 'Start Date') !!}
    {!! Form::text('start_at', date('m/d/Y',strtotime($milestone->start_at)), ['class' => 'form-control','id'=>'start_at']) !!}
</div>
<div class="form-group">
    {!! Form::label('due_at', 'Due Date') !!}
    {!! Form::text('due_at', date('m/d/Y',strtotime($milestone->due_at)), ['class' => 'form-control','id'=>'due_at']) !!}
</div>
<div class="form-group">
    {!! Form::label('end_at', 'Release Date') !!}
    {!! Form::text('end_at', date('m/d/Y',strtotime($milestone->end_at)), ['class' => 'form-control','id'=>'end_at']) !!}
</div>
<div class="form-group">
    {!! Form::label('name', 'Milestone Name') !!}
    {!! Form::text('name', $milestone->name, ['class' => 'form-control']) !!}
</div>
<div class="form-group">
    {!! Form::label('description', 'Milestone Description') !!}
    {!! Form::textarea('description', $milestone->description, ['class' => 'form-control summernote']) !!}
</div>
<div class="form-group">
{!! Form::submit('Save and Update Milestone', ['class' => 'btn btn-success pull-right']) !!}
</div>
{!! Form::close() !!}
@endsection
@section('javascript')
  <script src="/js/summernote.min.js"></script>
  <script>
      $(function() {

        $('.summernote').summernote({
    height: 300
              });

$("#due_at,#start_at").datepicker();

            });
</script>
@endsection
