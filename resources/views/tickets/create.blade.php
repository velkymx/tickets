@extends('layouts.app')

@section('title', 'Create Ticket')

@section('content')
    <h1 class="mb-3">Create a new Ticket</h1>
    <hr />

    {{-- The form now includes a hidden input for the Quill content --}}
    <form method="POST" action="{{ url('tickets') }}" id="ticket-form">
        @csrf {{-- CSRF protection --}}

        {{-- Subject Field --}}
        <div class="mb-3">
            <label for="subject" class="form-label">Ticket Subject</label>
            <input type="text" name="subject" id="subject" 
                   class="form-control @error('subject') is-invalid @enderror" 
                   placeholder="Ticket Subject" value="{{ old('subject') }}" required>
            @error('subject')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Ticket Details: The original textarea is kept, but will be HIDDEN. 
             We need this to hold the Quill content before submission. --}}
        <div class="mb-3">
            <label for="description" class="form-label">Ticket Details</label>
            
            {{-- This is the hidden input that will send the HTML content to Laravel --}}
            <input type="hidden" name="description" id="description_hidden" value="{{ old('description') }}">
            
            {{-- This is the DIV that Quill will target and render the rich text editor into --}}
            <div id="editor-container" class="editor-lg">
                {{ old('description') }}
            </div>
            @error('description')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        {{-- Use a grid for select fields for a cleaner, modern look --}}
        <div class="row g-3 mb-4">

            {{-- Ticket Type --}}
            <div class="col-md-6 col-lg-4">
                <label for="type_id" class="form-label">Ticket Type</label>
                <select name="type_id" id="type_id" class="form-select @error('type_id') is-invalid @enderror" required>
                    @foreach ($lookups['types'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('type_id', 3) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('type_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Ticket Importance --}}
            <div class="col-md-6 col-lg-4">
                <label for="importance_id" class="form-label">Ticket Importance</label>
                <select name="importance_id" id="importance_id" class="form-select @error('importance_id') is-invalid @enderror" required>
                    @foreach ($lookups['importances'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('importance_id', 2) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('importance_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Ticket Milestone --}}
            <div class="col-md-6 col-lg-4">
                <label for="milestone_id" class="form-label">Ticket Milestone</label>
                <select name="milestone_id" id="milestone_id" class="form-select @error('milestone_id') is-invalid @enderror" required>
                    @foreach ($lookups['milestones'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('milestone_id', 1) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('milestone_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Ticket Status --}}
            <div class="col-md-6 col-lg-4">
                <label for="status_id" class="form-label">Ticket Status</label>
                <select name="status_id" id="status_id" class="form-select @error('status_id') is-invalid @enderror" required>
                    @foreach ($lookups['statuses'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('status_id', 1) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('status_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Ticket Project --}}
            <div class="col-md-6 col-lg-4">
                <label for="project_id" class="form-label">Ticket Project</label>
                <select name="project_id" id="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                    @foreach ($lookups['projects'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('project_id', 4) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('project_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Assign To --}}
            <div class="col-md-6 col-lg-4">
                <label for="user_id2" class="form-label">Assign To</label>
                <select name="user_id2" id="user_id2" class="form-select @error('user_id2') is-invalid @enderror" required>
                    @foreach ($lookups['users'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('user_id2') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('user_id2')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
        </div> {{-- End row g-3 --}}
        
        <div class="row g-3 mb-4">
            
            {{-- Due Date --}}
            <div class="col-md-6">
                <label for="due_at" class="form-label">Due Date</label>
                <input type="date" name="due_at" id="due_at" class="form-control @error('due_at') is-invalid @enderror" value="{{ old('due_at') }}">
                @error('due_at')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            {{-- Time Estimate --}}
            <div class="col-md-6">
                <label for="estimate" class="form-label">Time Estimate (hours)</label>
                <input type="text" name="estimate" id="estimate" class="form-control @error('estimate') is-invalid @enderror" value="{{ old('estimate', 0) }}">
                @error('estimate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Save Button --}}
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Save Ticket</button>
        </div>
        
    </form>
@stop

@section('javascript')
{{-- Quill Initialization and Form Submission Logic --}}
<script type="module">
    document.addEventListener('DOMContentLoaded', async function() {
        const Quill = await window.loadQuill();
        
        const toolbarOptions = [
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'header': 1 }, { 'header': 2 }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'script': 'sub'}, { 'script': 'super' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'direction': 'rtl' }],
            [{ 'size': ['small', false, 'large', 'huge'] }],
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'font': [] }],
            [{ 'align': [] }],
            ['link', 'image'],
            ['clean']
        ];

        const quill = new Quill('#editor-container', {
            modules: { toolbar: toolbarOptions },
            theme: 'snow',
            placeholder: 'Enter ticket details here...'
        });
        
        const initialContent = document.getElementById('description_hidden').value;
        if (initialContent) {
            quill.clipboard.dangerouslyPasteHTML(initialContent);
        }

        const form = document.getElementById('ticket-form');
        const hiddenInput = document.getElementById('description_hidden');

        form.addEventListener('submit', function() {
            hiddenInput.value = quill.root.innerHTML;
        });
    });
</script>
@endsection