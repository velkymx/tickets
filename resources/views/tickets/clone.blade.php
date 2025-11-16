@extends('layouts.app')

@section('title', 'Clone to New Ticket')

@section('content')
    <h1 class="mb-3">Clone to New Ticket</h1>
    <hr />

    {{-- Form::open(['url'=>'tickets']) replaced with standard HTML form --}}
    <form method="POST" action="{{ url('tickets') }}" id="ticket-form">
        @csrf

        {{-- Subject Field (Cloned from existing ticket) --}}
        <div class="mb-3">
            <label for="subject" class="form-label visually-hidden">Ticket Subject</label>
            <input type="text" name="subject" id="subject" class="form-control" 
                   placeholder="Ticket Subject" value="{{ old('subject', $ticket->subject) }}" required>
        </div>

        {{-- Ticket Details (Quill.js Integration) --}}
        <div class="mb-3">
            <label for="description" class="form-label">Ticket Details</label>
            
            {{-- Hidden input to hold Quill content before submission --}}
            <input type="hidden" name="description" id="description_hidden" 
                   value="{{ old('description', $ticket->description) }}">
            
            {{-- Quill Editor Container. Initial content is loaded by the JS from the hidden input. --}}
            <div id="editor-container" style="height: 500px;">
            </div>
        </div>

        {{-- Use a Bootstrap 5 grid for cleaner layout --}}
        <div class="row g-3 mb-4">

            {{-- Ticket Type (Cloned) --}}
            <div class="col-md-6 col-lg-4">
                <label for="type_id" class="form-label">Ticket Type</label>
                <select name="type_id" id="type_id" class="form-select" required>
                    @foreach ($lookups['types'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('type_id', $ticket->type_id) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Ticket Importance (Cloned) --}}
            <div class="col-md-6 col-lg-4">
                <label for="importance_id" class="form-label">Ticket Importance</label>
                <select name="importance_id" id="importance_id" class="form-select" required>
                    @foreach ($lookups['importances'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('importance_id', $ticket->importance_id) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Ticket Milestone (Cloned) --}}
            <div class="col-md-6 col-lg-4">
                <label for="milestone_id" class="form-label">Ticket Milestone</label>
                <select name="milestone_id" id="milestone_id" class="form-select" required>
                    @foreach ($lookups['milestones'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('milestone_id', $ticket->milestone_id) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Ticket Status (Cloned) --}}
            <div class="col-md-6 col-lg-4">
                <label for="status_id" class="form-label">Ticket Status</label>
                <select name="status_id" id="status_id" class="form-select" required>
                    @foreach ($lookups['statuses'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('status_id', $ticket->status_id) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Ticket Project (Cloned) --}}
            <div class="col-md-6 col-lg-4">
                <label for="project_id" class="form-label">Ticket Project</label>
                <select name="project_id" id="project_id" class="form-select" required>
                    @foreach ($lookups['projects'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('project_id', $ticket->project_id) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Assign To (Cloned) --}}
            <div class="col-md-6 col-lg-4">
                <label for="user_id2" class="form-label">Assign To</label>
                <select name="user_id2" id="user_id2" class="form-select" required>
                    @foreach ($lookups['users'] as $id => $name)
                        <option value="{{ $id }}" @selected(old('user_id2', $ticket->user_id2) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
        </div> {{-- End row g-3 --}}
        
        <div class="row g-3 mb-4">
            
            {{-- Due Date (Cloned) --}}
            <div class="col-md-4">
                <label for="due_at" class="form-label">Due Date</label>
                <input type="text" name="due_at" id="due_at" class="form-control datepicker" 
                       value="{{ old('due_at', $ticket->due_at) }}">
            </div>
            
            {{-- Completed Date (Cloned) --}}
            <div class="col-md-4">
                <label for="closed_at" class="form-label">Completed Date</label>
                <input type="text" name="closed_at" id="closed_at" class="form-control datepicker" 
                       value="{{ old('closed_at', $ticket->closed_at) }}">
            </div>

            {{-- Time Estimate (Cloned) --}}
            <div class="col-md-4">
                <label for="estimate" class="form-label">Time Estimate (hours)</label>
                <input type="text" name="estimate" id="estimate" class="form-control" 
                       value="{{ old('estimate', $ticket->estimate) }}">
            </div>
        </div>

        {{-- Story Points was removed in the original template for clone, so it's omitted here. --}}

        {{-- Save Button (Replaced Form::submit and pull-right) --}}
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-success">Create Ticket</button>
        </div>
        
    </form>
@stop

@section('javascript')
{{-- Quill.js CSS and JS --}}
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. Datepicker Initialization ---
        // Retaining the standard approach for Datepicker, assuming a functional library is loaded.
        if (typeof jQuery !== 'undefined' && typeof jQuery.ui !== 'undefined' && typeof jQuery.ui.datepicker !== 'undefined') {
            $( ".datepicker" ).datepicker();
        } else {
            // Basic Vanilla JS placeholder logic if datepicker is not globally available
            document.querySelectorAll('.datepicker').forEach(input => {
                input.setAttribute('type', 'date');
            });
        }


        // --- 2. Quill Initialization ---
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
            placeholder: 'Enter ticket details here...'
        });
        
        // Load initial content (the cloned description)
        const initialContent = document.getElementById('description_hidden').value;
        if (initialContent) {
            quill.clipboard.dangerouslyPasteHTML(initialContent);
        }

        // --- 3. Form Submission Handler (Quill Content) ---
        const form = document.getElementById('ticket-form');
        const hiddenInput = document.getElementById('description_hidden');

        form.addEventListener('submit', function() {
            // Get the HTML content from the editor and put it into the hidden input
            hiddenInput.value = quill.root.innerHTML;
        });
    });
</script>
@endsection