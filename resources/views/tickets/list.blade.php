@extends('layouts.app')

@section('title', 'Ticket List')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Tickets</h1>
        <a href="/ticket/create" class="btn btn-sm btn-primary">Create Ticket</a>
    </div>

    {{-- Query Search --}}
    <x-ticket-search :query="$searchQuery" :tokens="$searchTokens" :action="url('tickets')" />

    {{-- Batch Update Form (Starts here, closes at the end of the section) --}}
    <form method="POST" action="{{ url('tickets/batch') }}">
        @csrf {{-- Add CSRF token for POST request --}}
        
        <x-ticket-table
            :tickets="$tickets"
            :paginator="$tickets"
            :sortable="true"
            :show-checkbox="true"
            :show-updated="true" />

        {{-- Check All Button: Replaced old 'btn btn-danger' --}}
        <button type="button" class="btn btn-outline-danger btn-sm" id="checkAll">Check All</button>

        <hr class="my-4">
        
        <h2>Batch Update Checked</h2>
        <div class="row g-3">
            {{-- Batch Update Fields: Replaced Form::group and Form::select with B5 markup --}}
            
            {{-- Ticket Type --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_type_id" class="form-label">Ticket Type</label>
                <select name="type_id" id="batch_type_id" class="form-select">
                    @foreach ($lookups['types'] as $id => $name)
                        <option value="{{ $id }}" @if (old('type_id') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Ticket Importance --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_importance_id" class="form-label">Ticket Importance</label>
                <select name="importance_id" id="batch_importance_id" class="form-select">
                    @foreach ($lookups['importances'] as $id => $name)
                        <option value="{{ $id }}" @if (old('importance_id') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Ticket Milestone --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_milestone_id" class="form-label">Ticket Milestone</label>
                <select name="milestone_id" id="batch_milestone_id" class="form-select">
                    @foreach ($lookups['milestones'] as $id => $name)
                        <option value="{{ $id }}" @if (old('milestone_id') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Ticket Status --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_status_id" class="form-label">Ticket Status</label>
                <select name="status_id" id="batch_status_id" class="form-select">
                    @foreach ($lookups['statuses'] as $id => $name)
                        <option value="{{ $id }}" @if (old('status_id') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Ticket Project --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_project_id" class="form-label">Ticket Project</label>
                <select name="project_id" id="batch_project_id" class="form-select">
                    @foreach ($lookups['projects'] as $id => $name)
                        <option value="{{ $id }}" @if (old('project_id') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Assign To --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_user_id2" class="form-label">Assign To</label>
                <select name="user_id2" id="batch_user_id2" class="form-select">
                    @foreach ($lookups['users'] as $id => $name)
                        <option value="{{ $id }}" @if (old('user_id2') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Add to Release --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_release_id" class="form-label">Add to Release</label>
                <select name="release_id" id="batch_release_id" class="form-select">
                    @foreach ($lookups['releases'] as $id => $name)
                        <option value="{{ $id }}" @if (old('release_id') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div> {{-- End Row g-3 --}}

        <div class="mt-4">
            <button type="submit" class="btn btn-primary float-end">Save and Update Checked Tickets</button>
        </div>

    </form>
    {{-- End Batch Update Form --}}

@endsection

@section('javascript')
<script>
    document.getElementById('checkAll').addEventListener('click', function() {
        // Select all checkboxes named 'tickets[]' within the form
        const checkboxes = document.querySelectorAll('input[name^="tickets["]');
        
        // Determine if we should check or uncheck them all
        // Check if ANY checkbox is currently NOT checked. If so, we want to check all.
        let shouldCheck = false;
        checkboxes.forEach(cb => {
            if (!cb.checked) {
                shouldCheck = true;
            }
        });

        // Set the checked state for all checkboxes
        checkboxes.forEach(cb => {
            cb.checked = shouldCheck;
        });

        // Optional: Update button text based on action
        this.textContent = shouldCheck ? 'Uncheck All' : 'Check All';
    });
</script>
@endsection