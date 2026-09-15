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
    {{-- My Tickets --}}
    <div class="col-lg-9">
        <h2 class="h5 mb-3">My Tickets</h2>
        <x-ticket-search :query="$searchQuery" :tokens="$searchTokens" :action="url('home')" />
        <x-ticket-table
            :tickets="$tickets"
            :paginator="$tickets"
            :sortable="true"
            :show-type="true"
            :show-updated="true"
            :small="true"
            empty-message="No tickets assigned to you." />
    </div>

    {{-- Sidebar: Recent Activity & Quick Links --}}
    <div class="col-lg-3">
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
                <h3 class="h6 mb-0"><i class="fa-regular fa-clock text-muted me-2"></i>Recent Activity</h3>
            </div>
            <div class="card-body p-0">
                @if($recentNotes->isNotEmpty())
                    <div class="list-group list-group-flush">
                        @foreach($recentNotes as $note)
                            <a href="/tickets/{{ $note->ticket->id }}" class="list-group-item list-group-item-action py-2">
                                <div class="d-flex gap-2 align-items-start">
                                    <i class="fa-regular fa-comment text-primary mt-1 flex-shrink-0"></i>
                                    <div class="flex-grow-1" style="min-width:0;">
                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                            <span class="badge text-bg-secondary">#{{ $note->ticket->id }}</span>
                                            <small class="text-muted text-nowrap flex-shrink-0">{{ $note->created_at->diffForHumans(['short' => true]) }}</small>
                                        </div>
                                        <p class="mb-0 mt-1 small text-body-secondary text-truncate">{{ Str::words(strip_tags($note->body), 12) }}</p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @elseif($recentTickets->isNotEmpty())
                    <div class="list-group list-group-flush">
                        @foreach($recentTickets as $ticket)
                            <a href="/tickets/{{ $ticket->id }}" class="list-group-item list-group-item-action py-2">
                                <div class="d-flex gap-2 align-items-start">
                                    <i class="{{ $ticket->type->icon }} text-{{ $ticket->importance->class }} mt-1 flex-shrink-0"></i>
                                    <div class="flex-grow-1" style="min-width:0;">
                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                            <span class="badge text-bg-{{ $ticket->importance->class }}">{{ $ticket->importance->name }}</span>
                                            <small class="text-muted text-nowrap flex-shrink-0">{{ $ticket->updated_at->diffForHumans(['short' => true]) }}</small>
                                        </div>
                                        <p class="mb-0 mt-1 small text-truncate">#{{ $ticket->id }} {{ $ticket->subject }}</p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="fa-regular fa-bell-slash mb-2" style="font-size: 1.5rem;"></i>
                        <p class="mb-0 small">No recent activity.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
