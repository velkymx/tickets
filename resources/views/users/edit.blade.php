@extends('layouts.app')
@section('title', 'Edit User')
@section('content')
<h1 class="mb-4">Edit Profile</h1>
<hr>

<form method="POST" action="/user/update" id="user_form">
    @csrf 

    <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name', $user->name) }}" placeholder="Full Name" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Job Title</label>
        <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" id="title" value="{{ old('title', $user->title) }}" placeholder="Web Developer" required>
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="editor-container" class="form-label">Bio</label>
        <div id="editor-container">
        </div>
        <input type="hidden" name="bio" id="bio-input" value="{{ old('bio', $user->bio) }}">
        @error('bio')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <h3 class="mt-5 mb-3">Contact Info</h3>
    
    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" value="{{ old('email', $user->email) }}" placeholder="Email" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="mb-3">
        <label for="phone" class="form-label">Phone Number</label>
        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="(343) 334-3423">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="timezone" class="form-label">Timezone</label>
        <select name="timezone" id="timezone" class="form-select @error('timezone') is-invalid @enderror">
            @foreach ($timezones as $timezone)
                @foreach($timezone as $key => $value)      
                <option value="{{ $key }}" @if (old('timezone', $user->timezone) == $key) selected @endif>{{ $value }}</option>
            @endforeach
            @endforeach
        </select>
        @error('timezone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="mb-3">
        <label for="theme" class="form-label">Theme</label>
        <select name="theme" id="theme" class="form-select @error('theme') is-invalid @enderror">
            @foreach ($themes as $key => $value)
                <option value="{{ $key }}" @if (old('theme', $user->theme) == $key) selected @endif>{{ $value }}</option>
            @endforeach
        </select>
        @error('theme')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex justify-content-start mt-4">
        <button type="submit" class="btn btn-success">Save Profile</button>
    </div>
</form>
@endsection

@section('javascript')
<script type="module">
    document.addEventListener('DOMContentLoaded', async function() {
        const Quill = await window.loadQuill();
        
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
@endsection