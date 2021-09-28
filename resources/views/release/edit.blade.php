@extends('layouts.app')
@section('title')
Releases List
@stop
<!-- Main Content -->
@section('content')
<h1>Edit Release</h1>
<hr>
{!! Form::open(['method' => 'POST', 'url' => '/release/edit/'.$release->id, 'class' => 'form','id'=>'formRelease']) !!}

<div class="form-group">
    {!! Form::label('title', 'Release Title') !!}
    {!! Form::text('title', $release->title, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('started_at', 'Start Date') !!}
            {!! Form::text('started_at', date_format(date_create($release->started_at),'m/d/Y'), ['class' => 'form-control datepicker', 'required' => 'required']) !!}
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('completed_at', 'Release Date') !!}
            {!! Form::text('completed_at', date_format(date_create($release->completed_at),'m/d/Y'), ['class' => 'form-control datepicker']) !!}
        </div>
    </div>    
</div>
<div class="form-group">
    {!! Form::label('body', 'Describe Release') !!}
    {!! Form::textarea('body', $release->body, ['class' => 'form-control summernote']) !!}
</div>
<div class="alert alert-info"><i class="fas fa-info-circle"></i> Add tickets to your release from the All Tickets tab using the bulk update feature.</div>
<div class="form-group">
    {!! Form::submit('Save Release', ['class' => 'btn btn-success pull-right']) !!}
</div>
{!! Form::close() !!}
@stop
@section('javascript')
<script>
    $(function() {
        $(".datepicker").datepicker();
        $("#formRelease").validate();
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
        height: 300,
        menubar: false,
    });
</script>
@stop