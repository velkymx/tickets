@extends('layouts.app')

@section('title', 'Kanban Board')

@section('content')

    <h1 class="mb-4">Kanban Board</h1>

    {{-- Same query bar as list view. Filters apply; columns stay, non-matches empty. --}}
    <x-ticket-search :query="$searchQuery ?? ''" :tokens="$searchTokens ?? []" :action="url('tickets/board')" />

    @if (! empty($searchQuery ?? ''))
        <div class="d-flex align-items-center gap-2 mb-3 small text-muted">
            <span>Showing {{ $tickets->count() }} filtered tickets.</span>
            <a href="{{ url('tickets/board') }}" class="text-decoration-none">Clear filter</a>
        </div>
    @endif

    @if ($tickets->isEmpty())
        <div class="alert alert-info">No tickets match this filter. <a href="{{ url('tickets/board') }}" class="alert-link">Clear it</a> to see the full board.</div>
    @endif

    {{-- Alert Container for AJAX updates (Vanilla JS will target this) --}}
    <div id="update-alert" class="alert alert-success alert-dismissible fade d-none" role="alert">
        <span id="update-message"></span>
        <button type="button" class="btn-close" aria-label="Close" id="close-alert-btn"></button>
    </div>

    {{-- The container for the board. We need horizontal scrolling. --}}
    <div class="d-flex overflow-auto pb-3"> 
        
        {{-- Iterate over all available statuses to create columns --}}
        @foreach ($lookups['statuses'] as $status_id => $status_name)
            @php
                $colTickets = $tickets->where('status_id', $status_id);
                $colCount = $colTickets->count();
                $wip = $wipLimits[$status_id] ?? null;
                $overWip = $wip !== null && $colCount > $wip;
                $isClosedCol = in_array($status_id, $closedStatusIds ?? [], true);
            @endphp
            {{-- Column Container (Replaced <table>/<td> and old panel styling) --}}
            <div class="me-4 flex-shrink-0 kanban-column">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-body-secondary">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <h5 class="mb-0">{{ $status_name }}</h5>
                            <span class="badge {{ $overWip ? 'text-bg-danger' : 'text-bg-secondary' }}" title="{{ $wip ? "WIP limit {$wip}" : 'Cards in column' }}">
                                {{ $colCount }}{{ $wip ? "/{$wip}" : '' }}
                            </span>
                        </div>
                        @if ($overWip)
                            <div class="small text-danger mt-1">Over WIP limit — finish work before pulling more.</div>
                        @endif
                    </div>

                    {{-- The List Container for SortableJS (must use a unique ID) --}}
                    <div class="card-body p-2 bg-body-tertiary">
                        <ol class="list-group list-group-flush ticket-column" data-status-id="{{ $status_id }}" id="status-{{ $status_id }}">

                            {{-- Iterate over tickets belonging to this status --}}
                            @foreach ($colTickets as $ticket)
                                @php
                                    $assigneeName = $ticket->assignee?->name;
                                    $initials = $assigneeName
                                        ? collect(preg_split('/\s+/', trim($assigneeName)))->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('')
                                        : null;
                                    $due = $ticket->due_at;
                                    $overdue = $due && $due->isPast() && ! $isClosedCol;
                                    $dueSoon = $due && ! $overdue && ! $isClosedCol && $due->isToday();
                                    $staleDays = (int) $ticket->updated_at->diffInDays(now());
                                    $stale = ! $isClosedCol && $staleDays >= 7;
                                @endphp
                                <li class="list-group-item list-group-item-action p-2 mb-2 rounded shadow-sm" data-ticket-id="{{ $ticket->id }}">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <a href="/tickets/{{ $ticket->id }}" class="text-decoration-none text-body fw-semibold flex-grow-1">
                                            <span class="text-muted">#{{ $ticket->id }}</span> {{ $ticket->subject }}
                                        </a>
                                        @if ($initials)
                                            <span class="kanban-avatar rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center flex-shrink-0" title="Assigned: {{ $assigneeName }}">{{ $initials }}</span>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        @if ($ticket->importance)
                                            <span class="badge text-bg-{{ $ticket->importance->class ?? 'secondary' }}" title="Importance: {{ $ticket->importance->name }}">
                                                @if (! empty($ticket->importance->icon))<i class="{{ $ticket->importance->icon }}"></i> @endif{{ $ticket->importance->name }}
                                            </span>
                                        @endif
                                        @if ($ticket->type)
                                            <span class="badge text-bg-light border" title="Type">{{ $ticket->type->name }}</span>
                                        @endif
                                        @if ($due)
                                            <span class="badge {{ $overdue ? 'text-bg-danger' : ($dueSoon ? 'text-bg-warning' : 'text-bg-light border') }}" title="Due {{ $due->toDateString() }}">
                                                <i class="fa-regular fa-calendar"></i> {{ $due->format('M j') }}
                                            </span>
                                        @endif
                                        @if ($stale)
                                            <span class="badge text-bg-warning" title="No update in {{ $staleDays }} days">stale {{ $staleDays }}d</span>
                                        @endif
                                        @if ($ticket->estimate)
                                            <span class="badge text-bg-light border" title="Estimate (h)"><i class="fa-regular fa-clock"></i> {{ rtrim(rtrim((string) $ticket->estimate, '0'), '.') }}h</span>
                                        @endif
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                                        <span class="d-inline-flex align-items-center gap-2">
                                            @if ($ticket->project)
                                                <span class="text-truncate kanban-project" title="{{ $ticket->project->name }}">{{ $ticket->project->name }}</span>
                                            @endif
                                            <span title="{{ $ticket->open_notes_count }} visible notes"><i class="fa-regular fa-comment"></i> {{ $ticket->open_notes_count }}</span>
                                        </span>
                                        <span title="{{ $ticket->updated_at->toDateTimeString() }}">{{ $ticket->updated_at->diffForHumans() }}</span>
                                    </div>
                                </li>
                            @endforeach

                            {{-- Add a placeholder item if column is empty for better drag-and-drop --}}
                            @if ($colTickets->isEmpty())
                                <li class="list-group-item list-group-item-light text-center fst-italic py-4" data-empty-placeholder>
                                    Drop tickets here
                                </li>
                            @endif
                        </ol>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@section('javascript')
    {{-- SortableJS CDN (Vanilla JS drag-and-drop) --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ticketColumns = document.querySelectorAll('.ticket-column');
            const alertDiv = document.getElementById('update-alert');
            const alertMessage = document.getElementById('update-message');
            const closeAlertBtn = document.getElementById('close-alert-btn');

            // --- 1. AJAX Status Update Function (Vanilla JS Fetch) ---
            function updateTicketStatus(ticketId, newStatusId) {
                // Remove any old placeholder before sending
                const placeholder = document.querySelector('[data-empty-placeholder]');
                if (placeholder) {
                    placeholder.remove();
                }

                const url = `/tickets/api/${ticketId}`;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: `status=${newStatusId}`,
                })
                    .then(response => response.json())
                    .then(data => {
                        alertMessage.textContent = `Ticket #${ticketId} updated.`;
                        alertDiv.classList.remove('d-none', 'fade');
                        alertDiv.classList.add('show');
                    })
                    .catch(error => {
                        alertMessage.textContent = `Error updating ticket ${ticketId}. See console for details.`;
                        alertDiv.classList.remove('alert-success', 'd-none');
                        alertDiv.classList.add('alert-danger', 'show');
                        console.error('API Update Error:', error);
                    });
            }

            // --- 2. Initialize SortableJS for each column ---
            ticketColumns.forEach(column => {
                const statusId = column.getAttribute('data-status-id');

                new Sortable(column, {
                    group: 'tickets-board', // Name to allow dragging between lists
                    animation: 150,
                    ghostClass: 'list-group-item-secondary', // Class for the ghost item
                    
                    // Event fired when an item is dropped into a new list
                    onEnd: function (evt) {
                        const ticketItem = evt.item;
                        const ticketId = ticketItem.getAttribute('data-ticket-id');
                        
                        // Check if the status actually changed
                        const oldList = evt.from;
                        const newList = evt.to;

                        if (oldList !== newList) {
                            const newStatusId = newList.getAttribute('data-status-id');
                            updateTicketStatus(ticketId, newStatusId);
                        }
                        
                        // Handle the empty state visually
                        checkEmptyColumn(oldList);
                        checkEmptyColumn(newList);
                    }
                });
            });

            // --- 3. Empty Column Placeholder Handler ---
            function checkEmptyColumn(listElement) {
                // Find all actual ticket items (those with data-ticket-id)
                const items = listElement.querySelectorAll('li[data-ticket-id]');
                let placeholder = listElement.querySelector('[data-empty-placeholder]');

                if (items.length === 0) {
                    // List is empty, add placeholder if it doesn't exist
                    if (!placeholder) {
                        const newPlaceholder = document.createElement('li');
                        newPlaceholder.className = 'list-group-item list-group-item-light text-center fst-italic py-4';
                        newPlaceholder.setAttribute('data-empty-placeholder', '');
                        newPlaceholder.textContent = 'Drop tickets here';
                        listElement.appendChild(newPlaceholder);
                    }
                } else {
                    // List has items, remove placeholder if it exists
                    if (placeholder) {
                        placeholder.remove();
                    }
                }
            }
            
            // --- 4. Alert Close Button Handler (Vanilla JS) ---
            closeAlertBtn.addEventListener('click', function() {
                alertDiv.classList.remove('show');
                setTimeout(() => {
                    alertDiv.classList.add('d-none');
                }, 150);
            });
            
            // Initial check for placeholders
            ticketColumns.forEach(checkEmptyColumn);
        });
    </script>
@endsection