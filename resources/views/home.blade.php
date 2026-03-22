@extends('layouts.app')
@section('title')
Dashboard
@stop
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Welcome, {{ auth()->user()->name }}</h1>
    <a href="/tickets/create" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i> Create Ticket
    </a>
</div>

{{-- Quick Stats --}}
<div class="row row-cols-2 row-cols-md-4 g-3 mb-4">
    <div class="col">
        <div class="card text-center h-100">
            <div class="card-body">
                <h2 class="display-6 fw-bold text-primary">{{ $stats['assigned'] }}</h2>
                <small class="text-muted">Assigned</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card text-center h-100">
            <div class="card-body">
                <h2 class="display-6 fw-bold text-success">{{ $stats['open'] }}</h2>
                <small class="text-muted">Open</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card text-center h-100">
            <div class="card-body">
                <h2 class="display-6 fw-bold text-secondary">{{ $stats['closed'] }}</h2>
                <small class="text-muted">Closed</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card text-center h-100">
            <div class="card-body">
                <h2 class="display-6 fw-bold text-warning">{{ $stats['watching'] }}</h2>
                <small class="text-muted">Watching</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Tickets by Status --}}
    <div class="col-lg-8">
        @if (count($alltickets) > 0)
            <h2 class="h5 mb-3">My Tickets by Status</h2>
            @foreach ($alltickets as $label => $tickets)
                <h3 class="h6 text-muted mb-2">{{ ucwords($label) }} ({{ $tickets->count() }})</h3>
                <x-ticket-table :tickets="$tickets" :show-checkbox="false" :show-type="true" :show-estimate="false" :show-created="false" :show-updated="true" :small="true" />
            @endforeach
        @else
            <div class="card">
                <div class="card-body text-center p-5">
                    <i class="fas fa-inbox text-muted mb-3" style="font-size: 3rem;"></i>
                    <p class="text-muted mb-3">No tickets assigned to you.</p>
                    <a href="/tickets/create" class="btn btn-primary">Create Your First Ticket</a>
                </div>
            </div>
        @endif
    </div>

    {{-- Sidebar: Recent Activity & Quick Links --}}
    <div class="col-lg-4">
        {{-- Quick Links --}}
        <div class="card mb-4">
            <div class="card-header bg-body-secondary">
                <h3 class="h6 mb-0">Quick Links</h3>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-2">
                    <a href="/tickets" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i> All Tickets
                    </a>
                    <a href="/tickets?user_id={{ auth()->id() }}" class="btn btn-outline-secondary">
                        <i class="fas fa-user me-2"></i> My Tickets
                    </a>
                    <a href="/tickets/board" class="btn btn-outline-secondary">
                        <i class="fas fa-columns me-2"></i> Kanban Board
                    </a>
                    <a href="/milestone" class="btn btn-outline-secondary">
                        <i class="fas fa-flag me-2"></i> Milestones
                    </a>
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="card">
            <div class="card-header bg-body-secondary">
                <h3 class="h6 mb-0">Recent Activity</h3>
            </div>
            <div class="card-body p-0">
                @if($recentNotes->isNotEmpty())
                    <div class="list-group list-group-flush">
                        @foreach($recentNotes as $note)
                            <a href="/tickets/{{ $note->ticket->id }}" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between align-items-start">
                                    <div>
                                        <span class="badge text-bg-secondary mb-1">#{{ $note->ticket->id }}</span>
                                        <p class="mb-1 small text-truncate">{{ Str::words(strip_tags($note->body), 8) }}</p>
                                    </div>
                                    <small class="text-muted">{{ $note->created_at->diffForHumans() }}</small>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @elseif($recentTickets->isNotEmpty())
                    <div class="list-group list-group-flush">
                        @foreach($recentTickets as $ticket)
                            <a href="/tickets/{{ $ticket->id }}" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between align-items-start">
                                    <div>
                                        <span class="badge text-bg-{{ $ticket->importance->class }} mb-1">{{ $ticket->importance->name }}</span>
                                        <p class="mb-1 small">{{ $ticket->subject }}</p>
                                    </div>
                                    <small class="text-muted">{{ $ticket->updated_at->diffForHumans() }}</small>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        <p class="mb-0 small">No recent activity.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
