@extends('layouts.app')
@section('title', $project->name . ' Ticket List')

@section('content')

{{-- Header and Edit Button --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">{{ $project->name }} Ticket List</h1>
    <a href="/projects/edit/{{ $project->id }}" class="btn btn-sm btn-primary">Edit Project</a>
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
        <div class="card text-center bg-body-secondary border shadow-sm h-100">
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
                    <h2 class="card-title mb-0">{{ $project->tickets->where('status_id',$code->id)->count() }}</h2>
                    <small class="text-muted">{{ $code->name }}</small>
                </div>
            </div>
        </div>
    @endforeach
</div>

<hr class="mb-4">

{{-- Ticket Table --}}
<x-ticket-table :tickets="$tickets->sortByDesc('importance_id')" :show-checkbox="false" :show-type="false" :show-estimate="false" :show-created="true" :show-updated="true" />

{{-- Pagination Links --}}
{!! $tickets->appends($queryfilter)->links('pagination::bootstrap-5') !!}
@endsection