@extends('layouts.app')
@section('title')
Edit User
@stop
@section('content')
<h1>Edit Profile</h1>
{{ Form::open(array('url' => 'user/update','id'=>'user_form')) }}
<div class="form-group">
    <label for="name">Name</label>
    <input type="text" class="form-control required" name="name" id="name" value="{{$user->name}}" placeholder="Full Name">
</div>
<div class="form-group">
    <label for="title">Job Title</label>
    <input type="text" class="form-control required" name="title" id="title" value="{{$user->title}}" placeholder="Web Developer">
</div>
{!! Form::label('bio', 'Bio') !!}
    {!! Form::textarea('bio', $user->bio, ['class' => 'form-control summernote']) !!}
    <h3>Contact Info</h3>
<div class="form-group">
    <label for="email">Email address</label>
    <input type="email" class="form-control required email" name="email" id="email" value="{{$user->email}}" placeholder="Email">
</div>
<div class="form-group">
    <label for="phone">Phone Number</label>
    <input type="text" class="form-control" id="phone" name="phone" value="{{$user->phone}}" placeholder="(343) 334-3423">
</div>
<div class="form-group">
    <label for="timezone">Timezone</label>
    {{Form::select('timezone', $timezones, $user->timezone,['class'=>'form-control'])}}
</div>
<div class="form-group">
    <label for="timezone">Theme</label>
    {{Form::select('theme', $themes, $user->theme,['class'=>'form-control'])}}
</div>
{!! Form::submit('Save Profile', ['class' => 'btn btn-success']) !!}  
{{ Form::close() }} 
@section('javascript') 
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.8.2/tinymce.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.8.2/icons/default/icons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.8.2/plugins/table/plugin.min.js"></script>
<script>
$(function() {
$("#user_form").validate();
})

tinymce.init({
    selector: '.summernote',
    plugins: ' preview paste searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern noneditable help charmap quickbars emoticons',     
    toolbar: 'bold italic underline strikethrough | forecolor backcolor removeformat | alignleft aligncenter alignright alignjustify | numlist bullist |  image link', 
    toolbar_sticky: true,
    height : 300,
    menubar: false,  
});
</script> 
<style>
    .error { color:red }
</style>
@stop
@stop