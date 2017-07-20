@extends('layouts.app')
@section('title')
Create Project
@stop
<!-- Main Content -->
@section('content')
<h1>Create New Project</h1>
<hr>
{!! Form::open(['method' => 'POST', 'url' => '/projects/store/new', 'class' => 'form-horizontal']) !!}

    <div class="form-group">
        {!! Form::label('name', 'Project Name') !!}
        {!! Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) !!}
    </div>
    <div class="form-group">
        {!! Form::label('description', 'Describe Project') !!}
        {!! Form::textarea('description', null, ['class' => 'form-control summernote']) !!}
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
