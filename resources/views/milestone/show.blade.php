@extends('layouts.app')
@section('title', $milestone->name . ' Milestone')

@section('content')
<h1 class="mb-3">{{ $milestone->name }} Milestone</h1>

{{-- Status Info --}}
@if ($milestone->end_at)
<p class="text-success">
    Started on {{ $milestone->start_at?->format('F jS, Y') ?? 'Unknown' }}, Released {{ $milestone->end_at?->format('F jS, Y') }}
</p>
@else
<p class="text-secondary">
    @if ($milestone->start_at)
    Unreleased Version - Started on {{ $milestone->start_at->format('F jS, Y') }}
    @else
    Unreleased Version - No start date set
    @endif
</p>
@endif
<hr class="mb-4">

<div class="row">
    {{-- Left Column: Tabs --}}
    <div class="col-lg-8">
 
        {{-- Ticket List (shared canonical component) --}}
        <x-ticket-filters :viewfilters="$viewfilters" :filter="$filter" :action="url('milestone/show/'.$milestone->id)" />
        <x-ticket-filter-tabs :counts="$tabCounts" />
        <x-ticket-table
            :tickets="$tickets"
            :paginator="$tickets"
            :sortable="true"
            :show-estimate="true"
            :show-updated="true" />
    </div>
    
    {{-- Right Column: Summary Sidebar --}}
    <div class="col-lg-4 mt-4 mt-lg-0">

        {{-- Action Buttons --}}
        <div class="row g-2 mb-4 text-center">
            <div class="col-6">
                <a href="/milestone/edit/{{ $milestone->id }}" class="btn btn-secondary w-100">
                    <i class="fas fa-edit"></i> Edit
                </a>
            </div>
            <div class="col-6">
                <a href="/milestone/report/{{ $milestone->id }}" class="btn btn-info w-100">
                    <i class="fas fa-chart-line"></i> Report
                </a>
            </div>
            <div class="col-6">
                <a href="/milestone/print/{{ $milestone->id }}" class="btn btn-secondary w-100">
                    <i class="fas fa-print"></i> Print
                </a>
            </div>
            <div class="col-6">
                @auth
                    @php
                        $isWatching = $milestone->watchers->contains('user_id', auth()->id());
                    @endphp
                    <form action="/milestone/watch/{{ $milestone->id }}" method="POST">
                        @csrf
                        <button type="submit" class="btn w-100 {{ $isWatching ? 'btn-danger' : 'btn-outline-secondary' }}">
                            {{ $isWatching ? 'Unwatch' : 'Watch' }}
                        </button>
                    </form>
                @endauth
            </div>
        </div>

        {{-- Replaced list-group structure with B5 cards for better grouping --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-body-secondary">
                Team Roles
            </div>
            <ul class="list-group list-group-flush">
                @if ($milestone->owner)
                    <li class="list-group-item"><strong>Product Owner:</strong> {{ $milestone->owner->name }}</li>
                @endif
                @if ($milestone->scrummaster)
                    <li class="list-group-item"><strong>Scrum Master:</strong> {{ $milestone->scrummaster->name }}</li>
                @endif
            </ul>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-body-secondary">
                Team Members
            </div>
            <ul class="list-group list-group-flush">
                @php $mem = []; @endphp
                @foreach ($milestone->tickets as $tick)
                    @if (!in_array($tick->assignee->name, $mem))
                        @php $mem[] = $tick->assignee->name; @endphp
                        <li class="list-group-item">
                            <a href="/users/{{ $tick->assignee->id }}" class="text-decoration-none text-body">
                                <i class="fas fa-user me-2"></i> {{ $tick->assignee->name }}
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-body-secondary">
                Sprint Summary
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">Total Tickets: <span class="badge text-bg-primary">{{ $milestone->tickets->count() }}</span></li>
                <li class="list-group-item">Est. Effort: <span class="badge text-bg-primary">{{ $milestone->tickets->sum('storypoints') }} SP</span></li>
                <li class="list-group-item">Est. Time: <span class="badge text-bg-primary">{{ $milestone->tickets->sum('estimate') }} Hrs</span></li>
                <li class="list-group-item text-success">
                    <strong>Progress: {{ $percent }}% Complete</strong>
                    {{-- Replaced old progress structure with B5 --}}
                     <div class="progress mt-2" style="height: 10px;">
                         <div class="progress-bar bg-success" 
                              role="progressbar" 
                              style="width:{{ $percent }}%;"
                              aria-valuenow="{{ $percent }}" 
                              aria-valuemin="0" 
                              aria-valuemax="100">
                         </div>
                     </div>
                </li>
                <li class="list-group-item">Closed Tickets: <span class="badge text-bg-success">{{ $milestone->tickets->whereIn('status_id', \App\Models\Status::closedStatusIds())->count() }}</span></li>
                <li class="list-group-item">Actual Time: <span class="badge text-bg-info">{{ $milestone->tickets->sum('actual') }} Hrs</span></li>
            </ul>
        </div>
    </div>
</div>
@endsection
