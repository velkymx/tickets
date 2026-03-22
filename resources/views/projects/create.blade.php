@extends('layouts.app')
@section('title', 'Create Project')

@section('content')
<h1 class="mb-4">Create New Project</h1>
<hr>

{{-- Replaced Form::open with standard HTML form. Using POST method. --}}
<form method="POST" action="/projects/store/new" class="form-horizontal" id="project_create_form">
    @csrf 
    {{-- Ensures CSRF protection for the POST request --}}
    <input type="hidden" name="id" value="new">

    {{-- Project Name Field --}}
    <div class="mb-3">
        <label for="name" class="form-label">Project Name</label>
        {{-- Replaced Form::text with standard input. 'null' is replaced by old('name') --}}
        <input type="text" name="name" id="name" value="{{ old('name') }}" 
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Project Description Field (Quill.js Target) --}}
    <div class="mb-3">
        <label for="editor-container" class="form-label">Describe Project</label>
        <textarea
            name="description"
            id="description-input"
            rows="8"
            class="form-control @error('description') is-invalid @enderror mb-3"
        >{{ old('description') }}</textarea>
        {{-- Quill editor container --}}
        <div id="editor-container" class="d-none">
            {{-- Initial content for a new project is empty, but we can load old input if the form fails validation --}}
        </div>
        @error('description')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Submit Button --}}
    <div class="d-flex justify-content-end mt-4">
        {{-- Replaced Form::submit and pull-right with standard button and d-flex --}}
        <button type="submit" class="btn btn-success">Save Project</button>
    </div>
</form>
@endsection

@section('javascript')
<script>
    document.addEventListener('DOMContentLoaded', async function() {
        if (typeof window.loadQuill !== 'function') {
            return;
        }

        const Quill = await window.loadQuill();
        if (!Quill) {
            return;
        }
        
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
        const editorContainer = document.getElementById('editor-container');

        if (!initialContentInput || !editorContainer) {
            return;
        }

        initialContentInput.classList.add('d-none');
        editorContainer.classList.remove('d-none');
        
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
