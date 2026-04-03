@extends('layouts.app')

@section('title', 'Create Milestone')

@section('content')
    <h1 class="mb-3">Create New Milestone</h1>
    <hr>

    {{-- Form::open replaced with standard HTML form --}}
    <form method="POST" action="{{ url('milestone/store/new') }}" class="form" id="milestone_form">
        @csrf

        <div class="row g-3">
            {{-- Start Date (Replaced Form::text and using native HTML date input) --}}
            <div class="col-md-6 mb-3">
                <label for="start_at" class="form-label">Start Date</label>
                {{-- Using type="date" for a native, no-JS-required date picker --}}
                <input type="date" name="start_at" id="start_at" 
                       class="form-control @error('start_at') is-invalid @enderror" 
                       value="{{ old('start_at', now()->format('Y-m-d')) }}" required>
                @error('start_at')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            {{-- Due Date (Replaced Form::text and using native HTML date input) --}}
            <div class="col-md-6 mb-3">
                <label for="due_at" class="form-label">Due Date</label>
                {{-- Using type="date" for a native, no-JS-required date picker --}}
                <input type="date" name="due_at" id="due_at" 
                       class="form-control @error('due_at') is-invalid @enderror" 
                       value="{{ old('due_at', now()->addWeeks(2)->format('Y-m-d')) }}">
                @error('due_at')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Milestone Name --}}
        <div class="mb-3">
            <label for="name" class="form-label">Milestone Name</label>
            <input type="text" name="name" id="name" 
                   class="form-control @error('name') is-invalid @enderror" 
                   value="{{ old('name') }}" required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Milestone Description (Quill.js Integration) --}}
        <div class="mb-3">
            <label for="description" class="form-label">Milestone Description</label>
            
            {{-- Hidden input to hold Quill content before submission --}}
            <input type="hidden" name="description" id="description_hidden" 
                   value="{{ old('description') }}">
            
            {{-- Quill Editor Container --}}
            <div id="editor-container" class="editor-lg">
                {{ old('description') }}
            </div>
            @error('description')
                <div class="invalid-feedback d-block">{{ $message }}</div>
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
                        <option value="{{ $id }}" @selected(old('owner_user_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('owner_user_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            {{-- Scrum Master / Sprint Manager --}}
            <div class="col-md-6">
                <label for="scrummaster_user_id" class="form-label">Scrum Master / Sprint Manager</label>
                <select name="scrummaster_user_id" id="scrummaster_user_id" 
                        class="form-select @error('scrummaster_user_id') is-invalid @enderror" required>
                    <option value="" disabled @selected(!old('scrummaster_user_id'))>Select Scrum Master</option>
                    @foreach ($users as $id => $name)
                        <option value="{{ $id }}" @selected(old('scrummaster_user_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('scrummaster_user_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Save Button (Replaced pull-right with flex utility) --}}
        <div class="d-flex justify-content-end">
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
            ['link', 'image'],
            ['clean']
        ];

        const quill = new Quill('#editor-container', {
            modules: { toolbar: quillToolbarOptions },
            theme: 'snow',
            placeholder: 'Enter milestone description here...'
        });
        
        const initialContent = document.getElementById('description_hidden').value;
        if (initialContent) {
            quill.clipboard.dangerouslyPasteHTML(initialContent);
        }

        const form = document.getElementById('milestone_form');
        const hiddenInput = document.getElementById('description_hidden');

        form.addEventListener('submit', function() {
            hiddenInput.value = quill.root.innerHTML;
        });
    });
</script>
@endsection