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
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.8.2/tinymce.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.8.2/icons/default/icons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.8.2/plugins/table/plugin.min.js"></script>
<script>
    tinymce.init({
        selector: '.summernote',
        plugins: ' preview paste searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern noneditable help charmap quickbars emoticons',
        toolbar: 'fontsizeselect formatselect | bold italic underline strikethrough | forecolor backcolor removeformat | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist |  image media link',
        toolbar_sticky: true,
        height: 300,
        menubar: false,
    });
</script>
@endsection
