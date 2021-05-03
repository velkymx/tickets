@extends('layouts.app')
@section('title')
Create Milestone
@stop
<!-- Main Content -->
@section('content')
<h1>Create New Milestone</h1>
<hr>
{!! Form::open(['method' => 'POST', 'url' => 'milestone/store/new', 'class' => 'form']) !!}
<div class="form-group">
    {!! Form::label('start_at', 'Start Date') !!}
    {!! Form::text('start_at', null, ['class' => 'form-control','id'=>'start_at']) !!}
</div>
<div class="form-group">
    {!! Form::label('due_at', 'Due Date') !!}
    {!! Form::text('due_at', null, ['class' => 'form-control','id'=>'due_at']) !!}
</div>
<div class="form-group">
    {!! Form::label('name', 'Milestone Name') !!}
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
</div>
<div class="form-group">
    {!! Form::label('description', 'Milestone Description') !!}
    {!! Form::textarea('description', null, ['class' => 'form-control summernote']) !!}
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

        $('.summernote').summernote({
    height: 300
              });

$("#due_at,#start_at").datepicker();

            });
</script>
@endsection
