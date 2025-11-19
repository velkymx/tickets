@extends('layouts.app')
@section('title', 'Edit Milestone')

@section('content')
<h1 class="mb-4">Edit Milestone</h1>
<hr>

{{-- FIXED: Must use method="POST" and include @method('PUT') for updates in Laravel --}}
<form method="POST" action="/milestone/update/{{ $milestone->id }}" class="form-horizontal" id="milestone_form">
    @csrf 
    @method('PUT') 
    {{-- This tells Laravel to treat the submission as an update (PUT) request --}}

    {{-- Milestone Name Field --}}
    <div class="mb-3">
        <label for="name" class="form-label">Milestone Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $milestone->name) }}" class="form-control" required>
    </div>

    {{-- Milestone Description Field (Quill.js Target) --}}
    <div class="mb-3">
        <label for="editor-container" class="form-label">Describe Milestone</label>
        {{-- Quill editor container --}}
        <div id="editor-container" style="height: 250px;">
            {{-- Initial content will be loaded by Quill.js script below --}}
        </div>
        {{-- Hidden input to hold the HTML content submitted by Quill. ID: 'description-input' --}}
        <input type="hidden" name="description" id="description-input" value="{{ old('description', $milestone->description) }}">
    </div>

    {{-- Active Status Field --}}
    <div class="mb-3">
        <label for="active" class="form-label">Active</label>
        <select name="active" id="active" class="form-select" required>
            <option value="0" @if (old('active', $milestone->active) == '0') selected @endif>Inactive</option>
            <option value="1" @if (old('active', $milestone->active) == '1') selected @endif>Active</option>
        </select>
    </div>

            <div class="row g-3 mb-4">
            {{-- Product Owner --}}
            <div class="col-md-6">
                <label for="owner_user_id" class="form-label">Product Owner</label>
                <select name="owner_user_id" id="owner_user_id" class="form-select" required>
                    <option value="" disabled @selected(!old('owner_user_id'))>Select Owner</option>
                    @foreach ($users as $id => $name)
                        <option value="{{ $id }}" @selected(old('owner_user_id', $milestone->owner_user_id) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Scrum Master / Sprint Manager --}}
            <div class="col-md-6">
                <label for="scrummaster_user_id" class="form-label">Scrum Master / Sprint Manager</label>
                <select name="scrummaster_user_id" id="scrummaster_user_id" class="form-select" required>
                    <option value="" disabled @selected(!old('scrummaster_user_id'))>Select Scrum Master</option>
                    @foreach ($users as $id => $name)
                        <option value="{{ $id }}" @selected(old('scrummaster_user_id', $milestone->scrummaster_user_id) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

    {{-- Submit Button --}}
    <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-success">Save Milestone</button>
    </div>
</form>
@endsection

@section('javascript')
{{-- Quill.js CSS and JS --}}
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
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
        
        if (initialContentInput && initialContentInput.value) {
            // Dangerously paste HTML content into the editor
            quill.clipboard.dangerouslyPasteHTML(initialContentInput.value);
        }

        // --- 2. Form Submission Handler (Quill Content) ---
        // FIXED: Using the correct form ID 'milestone_form' from the HTML
        const form = document.getElementById('milestone_form');
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