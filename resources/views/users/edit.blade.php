@extends('layouts.app')
@section('title', 'Edit User')
@section('content')
<h1 class="mb-4">Edit Profile</h1>
<hr>

<form method="POST" action="/user/update" id="user_form">
    @csrf 

    <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" class="form-control" name="name" id="name" value="{{ $user->name }}" placeholder="Full Name" required>
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Job Title</label>
        <input type="text" class="form-control" name="title" id="title" value="{{ $user->title }}" placeholder="Web Developer" required>
    </div>

    <div class="mb-3">
        <label for="editor-container" class="form-label">Bio</label>
        <div id="editor-container" style="height: 250px;">
        </div>
        <input type="hidden" name="bio" id="bio-input" value="{{ $user->bio }}">
    </div>

    <h3 class="mt-5 mb-3">Contact Info</h3>
    
    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control" name="email" id="email" value="{{ $user->email }}" placeholder="Email" required>
    </div>
    <div class="mb-3">
        <label for="phone" class="form-label">Phone Number</label>
        <input type="text" class="form-control" id="phone" name="phone" value="{{ $user->phone }}" placeholder="(343) 334-3423">
    </div>

    <div class="mb-3">
        <label for="timezone" class="form-label">Timezone</label>
        <select name="timezone" id="timezone" class="form-select">
            @foreach ($timezones as $timezone)
                @foreach($timezone as $key => $value)      
                <option value="{{ $key }}" @if ($user->timezone == $key) selected @endif>{{ $value }}</option>
            @endforeach
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label for="theme" class="form-label">Theme</label>
        <select name="theme" id="theme" class="form-select">
            @foreach ($themes as $key => $value)
                <option value="{{ $key }}" @if ($user->theme == $key) selected @endif>{{ $value }}</option>
            @endforeach
        </select>
    </div>

    <div class="d-flex justify-content-start mt-4">
        <button type="submit" class="btn btn-success">Save Profile</button>
    </div>
</form>
@endsection

@section('javascript')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const quillToolbarOptions = [
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }], 
            ['bold', 'italic', 'underline', 'strike'], 
            ['blockquote', 'code-block'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'color': [] }, { 'background': [] }], 
            ['link', 'image', 'video'],
            ['clean']
        ];

        const quill = new Quill('#editor-container', {
            modules: { toolbar: quillToolbarOptions },
            theme: 'snow',
            placeholder: 'Write a short bio about yourself here...'
        });
        
        const initialContentInput = document.getElementById('bio-input');
        
        if (initialContentInput && initialContentInput.value) {
            quill.clipboard.dangerouslyPasteHTML(initialContentInput.value);
        }

        const form = document.getElementById('user_form');
        const hiddenInput = initialContentInput;

        form.addEventListener('submit', function() {
            hiddenInput.value = quill.root.innerHTML;
        });
    });
</script>
<style>
    .error { color:red }
</style>
@endsection