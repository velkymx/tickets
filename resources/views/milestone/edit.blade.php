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

    <div class="row g-3">
        {{-- Start Date --}}
        <div class="col-md-6 mb-3">
            <label for="start_at" class="form-label">Start Date</label>
            <input type="date" name="start_at" id="start_at"
                   class="form-control @error('start_at') is-invalid @enderror"
                   value="{{ old('start_at', $milestone->start_at?->format('Y-m-d')) }}">
            @error('start_at')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Due Date --}}
        <div class="col-md-6 mb-3">
            <label for="due_at" class="form-label">Due Date</label>
            <input type="date" name="due_at" id="due_at"
                   class="form-control @error('due_at') is-invalid @enderror"
                   value="{{ old('due_at', $milestone->due_at?->format('Y-m-d')) }}">
            @error('due_at')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Milestone Name Field --}}
    <div class="mb-3">
        <label for="name" class="form-label">Milestone Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $milestone->name) }}" 
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Milestone Description Field (Quill.js Target) --}}
    <div class="mb-3">
        <label for="editor-container" class="form-label">Describe Milestone</label>
        {{-- Quill editor container --}}
        <div id="editor-container">
            {{-- Initial content will be loaded by Quill.js script below --}}
        </div>
        {{-- Hidden input to hold the HTML content submitted by Quill. ID: 'description-input' --}}
        <input type="hidden" name="description" id="description-input" value="{{ old('description', $milestone->description) }}">
        @error('description')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Active Status Field --}}
    <div class="mb-3">
        <label for="active" class="form-label">Active</label>
        <select name="active" id="active" class="form-select @error('active') is-invalid @enderror" required>
            <option value="0" @if (old('active', $milestone->active) == '0') selected @endif>Inactive</option>
            <option value="1" @if (old('active', $milestone->active) == '1') selected @endif>Active</option>
        </select>
        @error('active')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

            <div class="row g-3 mb-4">
            {{-- Product Owner --}}
            <div class="col-md-6">
                <label for="owner_user_id" class="form-label">Product Owner</label>
                <select name="owner_user_id" id="owner_user_id" 
                        class="form-select @error('owner_user_id') is-invalid @enderror" required>
                    <option value="" disabled @selected(!old('owner_user_id'))>Select Owner</option>
                    @foreach ($users as $id => $name)
                        <option value="{{ $id }}" @selected(old('owner_user_id', $milestone->owner_user_id) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('owner_user_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            {{-- Scrum Master / Milestone Manager --}}
            <div class="col-md-6">
                <label for="scrummaster_user_id" class="form-label">Scrum Master / Milestone Manager</label>
                <select name="scrummaster_user_id" id="scrummaster_user_id" 
                        class="form-select @error('scrummaster_user_id') is-invalid @enderror" required>
                    <option value="" disabled @selected(!old('scrummaster_user_id'))>Select Scrum Master</option>
                    @foreach ($users as $id => $name)
                        <option value="{{ $id }}" @selected(old('scrummaster_user_id', $milestone->scrummaster_user_id) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('scrummaster_user_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

    {{-- Submit Button --}}
    <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-success">Save Milestone</button>
    </div>
</form>
@endsection

@section('javascript')
<script type="module">
    document.addEventListener('DOMContentLoaded', async function() {
        const Quill = await window.loadQuill();
        
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
        
        const initialContentInput = document.getElementById('description-input');
        
        if (initialContentInput && initialContentInput.value) {
            quill.clipboard.dangerouslyPasteHTML(initialContentInput.value);
        }

        const form = document.getElementById('milestone_form');
        const hiddenInput = initialContentInput;

        form.addEventListener('submit', function() {
            hiddenInput.value = quill.root.innerHTML;
        });
    });
</script>
@endsection