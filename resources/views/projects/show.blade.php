@extends('layouts.app')
@section('title', $project->name . ' Ticket List')

@section('content')

{{-- Header and Edit Button --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>{{ $project->name }} Ticket List</h1>
    {{-- Replaced pull-right with d-flex utilities --}}
    <div>
       <a href="/projects/edit/{{ $project->id }}" class="btn btn-sm btn-primary">Edit Project</a>
    </div>
</div>

{{-- Progress Bar --}}
<h2 class="h5 mb-2">Progress: {{ $percent }}% Complete</h2>
<div class="progress mb-4" style="height: 25px;">
    {{-- Upgraded to B5 progress bar, including text inside --}}
    <div class="progress-bar bg-success" 
         role="progressbar" 
         style="width:{{ $percent }}%;" 
         aria-valuenow="{{ $percent }}" 
         aria-valuemin="0" 
         aria-valuemax="100">
         {{ $percent }}% Complete
    </div>
</div>

{{-- Ticket Status Summary Badges --}}
{{-- Replaced old row-fluid and col-xs-1 with B5 row/col and flex for alignment --}}
<div class="row row-cols-2 row-cols-md-4 row-cols-lg-auto g-3 mb-5">
    {{-- Total Tickets Badge (first column) --}}
    <div class="col">
        <div class="card text-center bg-light border shadow-sm h-100">
            <div class="card-body py-2">
                <h2 class="card-title mb-0">{{ $project->tickets->count() }}</h2>
                <small class="text-muted">Total Tickets</small>
            </div>
        </div>
    </div>
    
    {{-- Status Code Badges --}}
    @foreach ($statuscodes as $code)
        <div class="col">
            <div class="card text-center border h-100">
                <div class="card-body py-2">
                    <h2 class="card-title mb-0">{{ $project->tickets()->where('status_id',$code->id)->count() }}</h2>
                    <small class="text-muted">{{ $code->name }}</small>
                </div>
            </div>
        </div>
    @endforeach
</div>

<hr class="mb-4">

{{-- Ticket Table --}}
<div class="table-responsive">
    {{-- Replaced table-striped with B5 table-hover --}}
    <table class="table table-hover align-middle">
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
                {{-- Ticket Title and Type --}}
                <td class="text-{{ $tick->importance->class }}">
                    <i class="{{ $tick->type->icon }} me-1" title="{{ $tick->type->name }}"></i> 
                    <a href="/tickets/{{ $tick->id }}" class="text-decoration-none text-{{ $tick->importance->class }}">
                        #{{ $tick->id }} {{ $tick->subject }}
                    </a>
                </td>        
                {{-- Priority (P) Icon --}}
                <td>
                    <span class="text-{{ $tick->importance->class }}" title="Priority: {{ $tick->importance->name }}">
                        <i class="{{ $tick->importance->icon }}"></i>
                    </span>
                </td>
                {{-- Status Label --}}
                <td class="text-center">
                    {{-- Replaced old label class with B5 badge --}}
                    <span class="badge text-bg-secondary">{{ $tick->status->name }}</span>
                </td>
                <td>{{ $tick->project->name }}</td>
                <td>{{ $tick->assignee->name }}</td>
                {{-- Notes Badge --}}
                <td>
                    @if ($tick->notes()->where('hide','0')->where('notetype','message')->count() > 0)
                        <span class="badge text-bg-info">{{ $tick->notes()->where('hide','0')->where('notetype','message')->count() }}</span>
                    @endif
                </td>
                {{-- Dates --}}
                <td class="small text-muted">{{ date('M jS, Y g:ia', strtotime($tick->created_at)) }}</td>
                <td class="small text-muted">{{ date('M jS, Y g:ia', strtotime($tick->updated_at)) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Pagination Links --}}
{{-- NOTE: This Laravel pagination output will likely require custom styling to fully match Bootstrap 5.3 standards --}}
{!! $tickets->appends($queryfilter)->render() !!}
@endsection