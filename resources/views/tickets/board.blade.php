@extends('layouts.app')

@section('title', 'Ticket Board')

@section('content')

    <h1 class="mb-4">Ticket Board</h1>

    {{-- Alert Container for AJAX updates (Vanilla JS will target this) --}}
    <div id="update-alert" class="alert alert-success alert-dismissible fade" role="alert" style="display:none;">
        <span id="update-message"></span>
        <button type="button" class="btn-close" aria-label="Close" id="close-alert-btn"></button>
    </div>

    {{-- The container for the board. We need horizontal scrolling. --}}
    <div class="d-flex overflow-auto pb-3"> 
        
        {{-- Iterate over all available statuses to create columns --}}
        @foreach ($lookups['statuses'] as $status_id => $status_name)
            
            {{-- Column Container (Replaced <table>/<td> and old panel styling) --}}
            <div class="me-4 flex-shrink-0" style="width: 280px;">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">{{ $status_name }}</h5>
                    </div>
                    
                    {{-- The List Container for SortableJS (must use a unique ID) --}}
                    <div class="card-body p-2 bg-light-subtle">
                        <ol class="list-group list-group-flush ticket-column" data-status-id="{{ $status_id }}" id="status-{{ $status_id }}">
                            
                            {{-- Iterate over tickets belonging to this status --}}
                            @foreach ($tickets->where('status_id', $status_id) as $ticket)
                                <li class="list-group-item list-group-item-action p-2 mb-2 rounded shadow-sm bg-white" data-ticket-id="{{ $ticket->id }}">
                                    <a href="/tickets/{{ $ticket->id }}" class="text-decoration-none text-body">
                                        #{{ $ticket->id }} {{ $ticket->subject }}
                                    </a>
                                </li>
                            @endforeach
                            
                            {{-- Add a placeholder item if column is empty for better drag-and-drop --}}
                            @if ($tickets->where('status_id', $status_id)->isEmpty())
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

                const url = `/tickets/api/${ticketId}/?status=${newStatusId}`;

                fetch(url)
                    .then(response => response.text())
                    .then(data => {
                        // Display success message from the API call
                        alertMessage.textContent = `Ticket ${ticketId} updated. ${data}`;
                        alertDiv.classList.remove('fade');
                        alertDiv.classList.add('show');
                        alertDiv.style.display = 'block';
                    })
                    .catch(error => {
                        alertMessage.textContent = `Error updating ticket ${ticketId}. See console for details.`;
                        alertDiv.classList.remove('alert-success');
                        alertDiv.classList.add('alert-danger', 'show');
                        alertDiv.style.display = 'block';
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
                    alertDiv.style.display = 'none';
                }, 150);
            });
            
            // Initial check for placeholders
            ticketColumns.forEach(checkEmptyColumn);
        });
    </script>
@endsection