@extends('layouts.app')
@section('title')
Edit Project
@stop
<!-- Main Content -->
@section('content')
<h1>Edit Project</h1>
<hr>
{!! Form::open(['method' => 'POST', 'url' => '/projects/store/'.$project->id, 'class' => 'form-horizontal']) !!}

    <div class="form-group">
        {!! Form::label('name', 'Project Name') !!}
        {!! Form::text('name', $project->name, ['class' => 'form-control', 'required' => 'required']) !!}
    </div>
    <div class="form-group">
        {!! Form::label('description', 'Describe Project') !!}
        {!! Form::textarea('description', $project->description, ['class' => 'form-control summernote']) !!}
    </div>
    <div class="form-group">
        {!! Form::label('active', 'Active') !!}
        {!! Form::select('active', ['0'=>'Inactive','1'=>'Active'], $project->active, ['class' => 'form-control', 'required' => 'required']) !!}
    </div>
<div class="form-group">
{!! Form::submit('Save Project', ['class' => 'btn btn-success pull-right']) !!}
</div>
{!! Form::close() !!}
@stop
@section('javascript')
  <script src="/js/summernote.min.js"></script>
  <script>
      $(function() {

        $('.summernote').summernote({
    height: 300,
    onImageUpload: function(files, editor) {
                    sendFile(files[0],'.summernote');
                }
              });
            });
</script>
@endsection
