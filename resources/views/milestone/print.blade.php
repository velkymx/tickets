@extends('layouts.app')

@section('title')
{{ $milestone->name }} Ticket List
@endsection

@section('content')
    <h1 class="mb-3">{{ $milestone->name }}</h1>

    {{-- Milestone Status Info --}}
    @if ($milestone->end_at)
        <p class="text-success">
            Started on {{ $milestone->start_at->format('F jS, Y') }}, 
            Released {{ $milestone->end_at->format('F jS, Y') }}
        </p>
    @else
        <p class="text-secondary">
            Unreleased Version - Started on {{ date('F jS, Y', strtotime($milestone->start_at)) }}
        </p>
    @endif

    <hr class="mb-4">

    {{-- Project and Ticket Type Grouping --}}
    @foreach($projects as $project_id => $project_name)
        
        <h2 class="h3 mb-3">
            <i class="fas fa-folder me-2" aria-hidden="true"></i> {{ $project_name }}
        </h2>
        <hr class="mt-0 mb-4">

        @foreach($types as $type)
            @php
                // Fetch tickets specific to this milestone, project, and type, excluding closed/invalid statuses
                $tickets = $milestone->tickets()
                    ->where('type_id', $type->id)
                    ->whereNotIn('status_id', \App\Models\Status::closedStatusIds())
                    ->where('project_id', $project_id)
                    ->orderBy('subject')
                    ->get();
            @endphp
            
            @if($tickets->isNotEmpty())
                <div class="row mb-4">
                    {{-- Ticket Type Header (Bootstrap 5 col-md-3) --}}
                    <div class="col-md-3">
                        <h4 class="h5">
                            <i class="{{ $type->icon }} me-2" title="{{ $type->name }}"></i> {{ $type->name }}s
                        </h4>
                    </div>
                    
                    {{-- Ticket List (Bootstrap 5 col-md-9) --}}
                    <div class="col-md-9">
                        {{-- Using B5 list-group --}}
                        <ul class="list-group">
                            @foreach($tickets as $ticket)
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    {{ $ticket->subject }} 
                                    <a href="/tickets/{{ $ticket->id }}" class="ms-3 text-decoration-none badge text-bg-secondary">#{{ $ticket->id }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        @endforeach
    @endforeach

    <div class="mt-5 pt-3 border-top text-muted small">
        Milestone {{ $milestone->name }} Ticket List Generated {{ date('F dS, Y H:i') }}
    </div>
@endsection