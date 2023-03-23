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
  <script>
      $(function() {

          $("#milestone_form").validate();

            $("#due_at,#start_at").datepicker();

            });
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
</script>
@endsection
