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
            <label for="subject" class="form-label visually-hidden">Ticket Subject</label>
            <input type="text" name="subject" id="subject" class="form-control" 
                   placeholder="Ticket Subject" value="{{ old('subject') }}" required>
        </div>

        {{-- Ticket Details: The original textarea is kept, but will be HIDDEN. 
             We need this to hold the Quill content before submission. --}}
        <div class="mb-3">
            <label for="description" class="form-label">Ticket Details</label>
            
            {{-- This is the hidden input that will send the HTML content to Laravel --}}
            <input type="hidden" name="description" id="description_hidden" value="{{ old('description') }}">
            
            {{-- This is the DIV that Quill will target and render the rich text editor into --}}
            <div id="editor-container" style="height: 300px;">
                {{ old('description') }}
            </div>
        </div>

        {{-- Use a grid for select fields for a cleaner, modern look --}}
        <div class="row g-3 mb-4">

            {{-- Ticket Type --}}
            <div class="col-md-6 col-lg-4">
                <label for="type_id" class="form-label">Ticket Type</label>
                <select name="type_id" id="type_id" class="form-select" required>
                    @foreach ($lookups['types'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('type_id', 3) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Ticket Importance --}}
            <div class="col-md-6 col-lg-4">
                <label for="importance_id" class="form-label">Ticket Importance</label>
                <select name="importance_id" id="importance_id" class="form-select" required>
                    @foreach ($lookups['importances'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('importance_id', 2) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Ticket Milestone --}}
            <div class="col-md-6 col-lg-4">
                <label for="milestone_id" class="form-label">Ticket Milestone</label>
                <select name="milestone_id" id="milestone_id" class="form-select" required>
                    @foreach ($lookups['milestones'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('milestone_id', 1) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Ticket Status --}}
            <div class="col-md-6 col-lg-4">
                <label for="status_id" class="form-label">Ticket Status</label>
                <select name="status_id" id="status_id" class="form-select" required>
                    @foreach ($lookups['statuses'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('status_id', 1) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Ticket Project --}}
            <div class="col-md-6 col-lg-4">
                <label for="project_id" class="form-label">Ticket Project</label>
                <select name="project_id" id="project_id" class="form-select" required>
                    @foreach ($lookups['projects'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('project_id', 4) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Assign To --}}
            <div class="col-md-6 col-lg-4">
                <label for="user_id2" class="form-label">Assign To</label>
                <select name="user_id2" id="user_id2" class="form-select" required>
                    @foreach ($lookups['users'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('user_id2') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
        </div> {{-- End row g-3 --}}
        
        <div class="row g-3 mb-4">
            
            {{-- Due Date --}}
            <div class="col-md-6">
                <label for="due_at" class="form-label">Due Date</label>
                <input type="text" name="due_at" id="due_at" class="form-control datepicker" value="{{ old('due_at') }}">
            </div>
            
            {{-- Time Estimate --}}
            <div class="col-md-6">
                <label for="estimate" class="form-label">Time Estimate (hours)</label>
                <input type="text" name="estimate" id="estimate" class="form-control" value="{{ old('estimate', 0) }}">
            </div>
        </div>

        {{-- Save Button --}}
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-info">Save Ticket</button>
        </div>
        
    </form>
@stop

@section('javascript')
{{-- 1. Quill.js CSS and JS --}}
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

{{-- 2. Datepicker (assuming jQuery/jQuery UI are available in layouts.app) --}}
<script>
    $(function() {
      $( ".datepicker" ).datepicker();
    });
</script>

{{-- 3. Quill Initialization and Form Submission Logic --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- Quill Initialization ---
        const toolbarOptions = [
            ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
            ['blockquote', 'code-block'],

            [{ 'header': 1 }, { 'header': 2 }],               // custom button values
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'script': 'sub'}, { 'script': 'super' }],      // superscript/subscript
            [{ 'indent': '-1'}, { 'indent': '+1' }],          // outdent/indent
            [{ 'direction': 'rtl' }],                         // text direction

            [{ 'size': ['small', false, 'large', 'huge'] }],  // custom dropdown
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],

            [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
            [{ 'font': [] }],
            [{ 'align': [] }],
            
            ['link', 'image'], // added link and image handlers

            ['clean']                                         // remove formatting button
        ];

        const quill = new Quill('#editor-container', {
            modules: {
                toolbar: toolbarOptions
            },
            theme: 'snow', // snow theme is clean and standard
            placeholder: 'Enter ticket details here...'
        });
        
        // Load old content into the editor if it exists
        const initialContent = document.getElementById('description_hidden').value;
        if (initialContent) {
            // Using clipboard module to insert HTML content
            quill.clipboard.dangerouslyPasteHTML(initialContent);
        }

        // --- Form Submission Handler ---
        const form = document.getElementById('ticket-form');
        const hiddenInput = document.getElementById('description_hidden');

        form.addEventListener('submit', function() {
            // Get the content as HTML and set it to the hidden input
            // This ensures the rich content is sent to the backend
            hiddenInput.value = quill.root.innerHTML;
        });
    });
</script>
@endsection