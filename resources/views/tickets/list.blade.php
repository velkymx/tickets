@extends('layouts.app')

@section('title', 'Ticket List')

@section('content')
    <h1 class="mb-4">Tickets</h1>

    {{-- Filter Form (Replaces Form::open) --}}
    <form method="GET" action="{{ url('tickets') }}" class="mb-4">
        {{-- Bootstrap 5 uses 'row' and 'col' for layout, replacing the old table layout for forms --}}
        <div class="row g-2 align-items-end"> 
            
            <div class="col-auto">
                <span class="btn btn-outline-secondary disabled">Filter Tickets</span>
            </div>
            
            <div class="col-md-3 col-lg-3">
                <label for="q" class="form-label visually-hidden">Search</label>
                <input type="text" placeholder="Search" class="form-control" name="q" id="q" value="{{ request('q') }}">
            </div>
            
            <div class="col-md-auto">
                <label for="perpage" class="form-label visually-hidden"># Rows</label>
                <select name="perpage" id="perpage" class="form-select">
                    {{-- Retained the existing options, use selected attribute for existing value --}}
                    <option value="" @if(request('perpage') == '') selected @endif># Rows</option>
                    <option value="10" @if(request('perpage') == '10') selected @endif>10 Rows</option>
                    <option value="20" @if(request('perpage') == '20') selected @endif>20 Rows</option>
                    <option value="30" @if(request('perpage') == '30') selected @endif>30 Rows</option>
                    <option value="40" @if(request('perpage') == '40') selected @endif>40 Rows</option>
                    <option value="50" @if(request('perpage') == '50') selected @endif>50 Rows</option>
                </select>
            </div>
            
            {{-- Status Filter (Replaces Form::select) --}}
            <div class="col-md-auto">
                <label for="status_id" class="form-label visually-hidden">Status</label>
                <select name="status_id" id="status_id" class="form-select" required>
                    {{-- The $viewfilters['statuses'] should be an array of key => value (id => name) --}}
                    @foreach ($viewfilters['statuses'] as $id => $name)
                        <option value="{{ $id }}" @if ($filter['status_id'] == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Milestone Filter (Replaces Form::select) --}}
            <div class="col-md-auto">
                <label for="milestone_id" class="form-label visually-hidden">Milestone</label>
                <select name="milestone_id" id="milestone_id" class="form-select" required>
                    @foreach ($viewfilters['milestones'] as $id => $name)
                        <option value="{{ $id }}" @if ($filter['milestone_id'] == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Type Filter (Replaces Form::select) --}}
            <div class="col-md-auto">
                <label for="type_id" class="form-label visually-hidden">Type</label>
                <select name="type_id" id="type_id" class="form-select" required>
                    @foreach ($viewfilters['types'] as $id => $name)
                        <option value="{{ $id }}" @if ($filter['type_id'] == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Refresh Rows</button>
            </div>
        </div>
    </form>
    {{-- End Filter Form --}}

    {{-- Batch Update Form (Starts here, closes at the end of the section) --}}
    <form method="POST" action="{{ url('tickets/batch') }}">
        @csrf {{-- Add CSRF token for POST request --}}
        
        <div class="table-responsive">
            <table class="table table-striped align-middle"> {{-- Added align-middle for vertical alignment --}}
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>P</th>
                        <th>Status</th>
                        <th>Project</th>
                        <th>Assignee</th>
                        <th>Notes</th>
                        <th>Created</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets->sortByDesc('importance_id') as $tick)
                        <tr>
                            {{-- Checkbox and Title --}}
                            <td class="text-{{$tick->importance->class}}">
                                <input type="checkbox" name="tickets[{{$tick->id}}]" value="{{$tick->id}}" class="form-check-input me-1"> 
                                <i class="{{$tick->type->icon}}" title="{{$tick->type->name}}"></i> 
                                <a href="/tickets/{{$tick->id}}" class="text-{{$tick->importance->class}} text-decoration-none">
                                    #{{$tick->id}} {{ $tick->subject }}
                                </a>
                            </td>
                            
                            {{-- Priority (P) --}}
                            <td>
                                <span class="text-{{$tick->importance->class}}" title="Priority: {{$tick->importance->name}}">
                                    <i class="{{$tick->importance->icon}}"></i>
                                </span>
                            </td>
                            
                            {{-- Status: Replaced old 'label' class with Bootstrap 5 'badge' --}}
                            <td class="text-center">
                                <span class="badge text-bg-secondary">{{$tick->status->name}}</span>
                            </td>
                            
                            {{-- Project --}}
                            <td>{{$tick->project->name}}</td>
                            
                            {{-- Assignee --}}
                            <td>{{$tick->assignee->name}}</td>
                            
                            {{-- Notes --}}
                            <td>
                                @php
                                    $noteCount = $tick->notes()->where('hide','0')->where('notetype','message')->count();
                                @endphp
                                @if ($noteCount > 0)
                                    <span class="badge text-bg-info rounded-pill">{{ $noteCount }}</span>
                                @endif
                            </td>
                            
                            {{-- Created --}}
                            <td>{{date('M jS, Y g:ia',strtotime($tick->created_at))}}</td>
                            
                            {{-- Updated --}}
                            <td>{{date('M jS, Y g:ia',strtotime($tick->updated_at))}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination Links --}}
        <div class="d-flex justify-content-center my-3">
            {!! $tickets->appends($queryfilter)->links('pagination::bootstrap-5') !!}
        </div>

        {{-- Check All Button: Replaced old 'btn btn-danger' --}}
        <button type="button" class="btn btn-outline-danger btn-sm" id="checkAll">Check All</button>

        <hr class="my-4">
        
        <h2>Batch Update Checked</h2>
        <div class="row g-3">
            {{-- Batch Update Fields: Replaced Form::group and Form::select with B5 markup --}}
            
            {{-- Ticket Type --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_type_id" class="form-label">Ticket Type</label>
                <select name="type_id" id="batch_type_id" class="form-select" required>
                    @foreach ($lookups['types'] as $id => $name)
                        <option value="{{ $id }}" @if (old('type_id') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Ticket Importance --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_importance_id" class="form-label">Ticket Importance</label>
                <select name="importance_id" id="batch_importance_id" class="form-select" required>
                    @foreach ($lookups['importances'] as $id => $name)
                        <option value="{{ $id }}" @if (old('importance_id') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Ticket Milestone --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_milestone_id" class="form-label">Ticket Milestone</label>
                <select name="milestone_id" id="batch_milestone_id" class="form-select" required>
                    @foreach ($lookups['milestones'] as $id => $name)
                        <option value="{{ $id }}" @if (old('milestone_id') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Ticket Status --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_status_id" class="form-label">Ticket Status</label>
                <select name="status_id" id="batch_status_id" class="form-select" required>
                    @foreach ($lookups['statuses'] as $id => $name)
                        <option value="{{ $id }}" @if (old('status_id') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Ticket Project --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_project_id" class="form-label">Ticket Project</label>
                <select name="project_id" id="batch_project_id" class="form-select" required>
                    @foreach ($lookups['projects'] as $id => $name)
                        <option value="{{ $id }}" @if (old('project_id') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Assign To --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_user_id2" class="form-label">Assign To</label>
                <select name="user_id2" id="batch_user_id2" class="form-select" required>
                    @foreach ($lookups['users'] as $id => $name)
                        <option value="{{ $id }}" @if (old('user_id2') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Add to Release --}}
            <div class="col-md-6 col-lg-4">
                <label for="batch_release_id" class="form-label">Add to Release</label>
                <select name="release_id" id="batch_release_id" class="form-select" required>
                    @foreach ($lookups['releases'] as $id => $name)
                        <option value="{{ $id }}" @if (old('release_id') == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div> {{-- End Row g-3 --}}

        <div class="mt-4">
            <button type="submit" class="btn btn-info float-end">Save and Update Checked Tickets</button>
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