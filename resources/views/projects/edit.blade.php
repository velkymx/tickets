@extends('layouts.app')
@section('title', 'Edit Project')

@section('content')
<h1 class="mb-4">Edit Project</h1>
<hr>

{{-- Replaced Form::open with standard HTML form --}}
<form method="POST" action="/projects/store/{{ $project->id }}" class="form-horizontal" id="project_edit_form">
    @csrf 
    {{-- Assuming CSRF is handled by Laravel's @csrf directive --}}
    <input type="hidden" name="id" value="{{ $project->id }}">

    {{-- Project Name Field --}}
    <div class="mb-3">
        <label for="name" class="form-label">Project Name</label>
        {{-- Replaced Form::text with standard input --}}
        <input type="text" name="name" id="name" value="{{ old('name', $project->name) }}" 
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
        >{{ old('description', $project->description) }}</textarea>
        {{-- Quill editor container --}}
        <div id="editor-container" class="d-none">
            {!! clean(old('description', $project->description)) !!}
        </div>
        @error('description')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Active Status Field --}}
    <div class="mb-3">
        <label for="active" class="form-label">Active</label>
        {{-- Replaced Form::select with standard select --}}
        <select name="active" id="active" class="form-select @error('active') is-invalid @enderror" required>
            <option value="0" @if (old('active', $project->active) == '0') selected @endif>Inactive</option>
            <option value="1" @if (old('active', $project->active) == '1') selected @endif>Active</option>
        </select>
        @error('active')
            <div class="invalid-feedback">{{ $message }}</div>
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
            ['bold', 'italic', 'underline', 'strike'], 
            ['blockquote', 'code-block'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            ['link', 'image'],
            ['clean']
        ];

        const quill = new Quill('#editor-container', {
            modules: { toolbar: quillToolbarOptions },
            theme: 'snow',
            placeholder: 'Enter milestone details here...'
        });
        
        // Load initial content from the hidden input/old value
        // FIXED: Using the correct ID 'description-input' from the HTML
        const initialContentInput = document.getElementById('description-input');
        const editorContainer = document.getElementById('editor-container');

        if (!initialContentInput || !editorContainer) {
            return;
        }

        initialContentInput.classList.add('d-none');
        editorContainer.classList.remove('d-none');
        
        if (initialContentInput && initialContentInput.value) {
            // Dangerously paste HTML content into the editor
            quill.clipboard.dangerouslyPasteHTML(initialContentInput.value);
        }

        // --- 2. Form Submission Handler (Quill Content) ---
        // FIXED: Using the correct form ID 'milestone_form' from the HTML
        const form = document.getElementById('project_edit_form');
        if (!form) {
            return;
        }
        const hiddenInput = initialContentInput;

        form.addEventListener('submit', function() {
            // Get the HTML content from the editor and put it into the hidden input
            hiddenInput.value = quill.root.innerHTML;
        });
        
        // NOTE: The jQuery Datepicker block was removed to avoid using jQuery 
        // and because there are no date fields in this form.
    });
</script>
@endsection
