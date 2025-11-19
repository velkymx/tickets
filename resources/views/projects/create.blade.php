@extends('layouts.app')
@section('title', 'Create Project')

@section('content')
<h1 class="mb-4">Create New Project</h1>
<hr>

{{-- Replaced Form::open with standard HTML form. Using POST method. --}}
<form method="POST" action="/projects/store/new" class="form-horizontal" id="project_create_form">
    @csrf 
    {{-- Ensures CSRF protection for the POST request --}}

    {{-- Project Name Field --}}
    <div class="mb-3">
        <label for="name" class="form-label">Project Name</label>
        {{-- Replaced Form::text with standard input. 'null' is replaced by old('name') --}}
        <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control" required>
    </div>

    {{-- Project Description Field (Quill.js Target) --}}
    <div class="mb-3">
        <label for="editor-container" class="form-label">Describe Project</label>
        {{-- Quill editor container --}}
        <div id="editor-container" style="height: 250px;">
            {{-- Initial content for a new project is empty, but we can load old input if the form fails validation --}}
        </div>
        {{-- Hidden input to hold the HTML content submitted by Quill. ID: 'description-input' --}}
        <input type="hidden" name="description" id="description-input" value="{{ old('description') }}">
    </div>

    {{-- Submit Button --}}
    <div class="d-flex justify-content-end mt-4">
        {{-- Replaced Form::submit and pull-right with standard button and d-flex --}}
        <button type="submit" class="btn btn-success">Save Project</button>
    </div>
</form>
@endsection

@section('javascript')
{{-- Load Quill CSS using the specified CDN path --}}
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
{{-- Load Quill.js 2.0.3 using the specified CDN path --}}
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. Quill Initialization ---
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
            placeholder: 'Enter the project description here...'
        });
        
        // Load old input if available (e.g., if the form failed validation)
        const initialContentInput = document.getElementById('description-input');
        
        if (initialContentInput && initialContentInput.value) {
            quill.clipboard.dangerouslyPasteHTML(initialContentInput.value);
        }

        // --- 2. Form Submission Handler (Quill Content) ---
        const form = document.getElementById('project_create_form');
        const hiddenInput = initialContentInput;

        form.addEventListener('submit', function() {
            // Get the HTML content from the editor and put it into the hidden input
            hiddenInput.value = quill.root.innerHTML;
        });
    });
</script>
@endsection