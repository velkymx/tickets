@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id)

@section('content')

    {{-- Bootstrap 5 Breadcrumb --}}
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/tickets/">Tickets</a></li>
            <li class="breadcrumb-item active" aria-current="page">Ticket #{{ $ticket->id }}</li>
        </ol>
    </nav>

    {{-- Laravel Session Status Alert --}}
    @if (session()->has('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Status Alerts (Replaced old Bootstrap 3 styling) --}}
    @if($ticket->closed_at)
        <div class="alert alert-danger" role="alert">
            <strong>Closed:</strong> This ticket was closed {{ date('m/d/Y g:ia', strtotime($ticket->closed_at)) }}
        </div>
    @endif

    @if($ticket->assignee->name == 'Unassigned')
        <div class="alert alert-warning d-flex justify-content-between align-items-center" role="alert"> 
            <span>This Ticket is currently unassigned.</span>
            <form action="/tickets/claim/{{ $ticket->id }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">Claim Ticket</button>
            </form>
        </div>
    @endif

    {{-- Ticket Pulse (Real-Time Decision Log) --}}
    <x-ticket-pulse :ticket="$ticket" :pulse="$pulse->toArray()" />

    <div class="row">
        {{-- Left Column (Ticket Body, Notes, Update Form) --}}
        <div class="col-lg-8">
            <h2 class="mb-3">
                <i class="{{ $ticket->type->icon }}" title="{{ $ticket->type->name }}" aria-hidden="true"></i> 
                {{ $ticket->subject }}
            </h2>

            <div id="ticket_body">
                @if($ticket->description)
                    <hr>
                    <div class="ticket-description-content">
                        {!! clean($ticket->description) !!}
                    </div>
                @else
                    {{-- Replaced panel panel-default with card --}}
                    <div class="card card-body text-muted">
                        No Ticket Body Provided
                    </div>
                @endif
                <div class="my-5"></div>
            </div>

            {{-- Bootstrap 5 Nav Tabs --}}
            <ul class="nav nav-tabs mb-3" id="ticketTabs" role="tablist">
                <li class="nav-item" role="presentation">
                     <button class="nav-link active" id="messages-tab" data-bs-toggle="tab" data-bs-target="#messages" type="button" role="tab" aria-controls="messages" aria-selected="true">
                         Notes ({{ $ticket->notes->where('hide', '0')->where('notetype', 'message')->count() }})
                     </button>
                 </li>
                 <li class="nav-item" role="presentation">
                     <button class="nav-link" id="changelog-tab" data-bs-toggle="tab" data-bs-target="#changelog" type="button" role="tab" aria-controls="changelog" aria-selected="false">
                         Changelog ({{ $ticket->notes->where('hide', '0')->where('notetype', 'changelog')->count() }})
                     </button>
                </li>
            </ul>
            
            <div class="tab-content" id="ticketTabsContent">
                
                 {{-- Messages Tab Panel --}}
                 <div class="tab-pane fade show active" id="messages" role="tabpanel" aria-labelledby="messages-tab">
                     @php
                         $messageNotes = $ticket->notes->where('hide','0')->where('notetype','message');
                     @endphp
                     @if ($messageNotes->isEmpty())
                         <div class="card card-body text-muted">No Notes Found</div>
                     @else
                         @foreach ($messageNotes as $note)
                             {{-- Replaced panel panel-default with card mb-3 --}}
                             <div class="card mb-3" id="note_{{ $note->id }}">
                                 <div class="card-header bg-body-secondary">
                                     <strong><a href="/users/{{ $note->user->id }}">{{ $note->user->name }}</a></strong> | posted {{ date('M jS, Y g:ia', strtotime($note->created_at)) }}
                                     <button onclick="hideNote('{{ $note->id }}');" class="btn btn-outline-danger btn-sm float-end">Remove</button>
                                 </div>
                                 <div class="card-body">
                                     {!! clean($note->body) !!}
                                 </div>
                             </div>
                         @endforeach
                     @endif
                 </div>

                 {{-- Changelog Tab Panel --}}
                 <div class="tab-pane fade" id="changelog" role="tabpanel" aria-labelledby="changelog-tab">
                     @php
                         $changelogNotes = $ticket->notes->where('hide','0')->where('notetype','changelog');
                     @endphp
                    @if ($changelogNotes->isEmpty())
                        <div class="card card-body text-muted">No Changelog Entries Found</div>
                    @else
                        @foreach ($changelogNotes as $change)
                            <div class="card mb-3" id="note_{{ $change->id }}">
                                <div class="card-header bg-body-secondary">
                                    <strong><a href="/users/{{ $change->user->id }}">{{ $change->user->name }}</a></strong> changed ticket {{ date('M jS, Y g:ia', strtotime($change->created_at)) }}
                                    <button onclick="hideNote('{{ $change->id }}');" class="btn btn-outline-danger btn-sm float-end">Remove</button>
                                </div>
                                <div class="card-body">
                                    {!! clean($change->body) !!}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <hr class="mt-4 mb-4" />
            
            {{-- Ticket Update/Note Form --}}
            <form method="POST" action="{{ url('notes') }}" id="note-update-form">
                @csrf
                
                {{-- Rich Text Editor (Quill.js) --}}
                <div class="mb-3">
                    <label for="body" class="form-label">Status Update and Notes</label>
                    {{-- Hidden input for Quill content --}}
                    <input type="hidden" name="body" id="note_body_hidden">
                    {{-- Quill Editor Container --}}
                    <div id="note-editor-container" class="editor-sm"></div>
                </div>

                {{-- Change Status (Replaces Form::select) --}}
                <div class="mb-3">
                    <label for="status_id" class="form-label">Change Status</label>
                    <select name="status_id" id="status_id" class="form-select" required>
                        @foreach ($lookups['statuses'] as $id => $name)
                            <option value="{{ $id }}" @selected($ticket->status->id == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                
                {{-- Add Time/Quantity (Replaces Form::text) --}}
                <div class="mb-3">
                    <label for="hours" class="form-label">Add Time or Quantity (hours)</label>
                    <input type="text" name="hours" id="hours" class="form-control" value="{{ old('hours', 0) }}" required>
                </div>

                <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                
                <button type="submit" class="btn btn-success">Save Note</button>
            </form>
        </div>

        {{-- Right Column (Details) --}}
        <div class="col-lg-4 mt-4 mt-lg-0">
            
            {{-- Action Buttons --}}
            <div class="row g-2 mb-4 text-center">
                <div class="col-6">
                    <a href="/tickets/edit/{{ $ticket->id }}" class="btn btn-secondary w-100">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
                <div class="col-6">
                    <a href="/tickets/clone/{{ $ticket->id }}" class="btn btn-secondary w-100">
                        <i class="fas fa-copy"></i> Clone
                    </a>
                </div>
                <div class="col-6">
                    @auth
                        @php
                            $isWatching = $ticket->watchers->contains('user_id', auth()->id());
                        @endphp
                        <form action="/tickets/watch/{{ $ticket->id }}" method="POST">
                            @csrf
                            <button type="submit" class="btn w-100 {{ $isWatching ? 'btn-danger' : 'btn-outline-secondary' }}">
                                {{ $isWatching ? 'Unwatch' : 'Watch' }}
                            </button>
                        </form>
                    @endauth
                </div>
            </div>

            {{-- Ticket Details Card --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-body-secondary">
                    Ticket Details
                </div>
                <ul class="list-group list-group-flush">
                    @if ($ticket->due_at)
                        <li class="list-group-item">
                            <strong>Due:</strong> {{ date('M jS, Y', strtotime($ticket->due_at)) }}
                        </li>
                    @endif
                    <li class="list-group-item">
                        <strong>Importance:</strong>
                        <span class="text-{{ $ticket->importance->class }}">
                            <i class="{{ $ticket->importance->icon }}"></i> {{ $ticket->importance->name }}
                        </span>
                    </li>
                    <li class="list-group-item">
                        <strong>Status:</strong> <span class="badge text-bg-secondary">{{ $ticket->status->name }}</span>
                    </li>
                    <li class="list-group-item">
                        <strong>Type:</strong> <i class="{{ $ticket->type->icon }}"></i> {{ $ticket->type->name }}
                    </li>
                    <li class="list-group-item">
                        <strong>Assignee:</strong>
                        <a href="/users/{{ $ticket->assignee->id }}" class="text-decoration-none">{{ $ticket->assignee->name }}</a>
                    </li>
                    <li class="list-group-item">
                        <strong>Owner:</strong>
                        <a href="/users/{{ $ticket->user->id }}" class="text-decoration-none">{{ $ticket->user->name }}</a>
                    </li>
                </ul>
            </div>

            {{-- Project & Milestone Card --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-body-secondary">
                    Project & Milestone
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>Project:</strong>
                        <a href="/projects/show/{{ $ticket->project->id }}" class="text-decoration-none">{{ $ticket->project->name }}</a>
                    </li>
                    <li class="list-group-item">
                        <strong>Milestone:</strong>
                        <a href="/milestone/show/{{ $ticket->milestone->id }}" class="text-decoration-none">{{ $ticket->milestone->name }}</a>
                    </li>
                </ul>
            </div>

            {{-- Effort & Estimates Card --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-body-secondary d-flex justify-content-between align-items-center">
                    Effort & Estimates
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#estimateModal">
                        Estimate
                    </button>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        Story Points: <span class="badge text-bg-primary">{{ $ticket->storypoints }} Points</span>
                    </li>
                    @if($ticket->estimates->isNotEmpty())
                        @foreach($ticket->estimates as $usp)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $usp->storypoints }} points
                                <span class="badge text-bg-secondary">{{ $usp->user->name }}</span>
                            </li>
                        @endforeach
                    @endif
                    <li class="list-group-item">
                        Time Estimate: <span class="badge text-bg-primary">{{ $ticket->estimate }} Hrs</span>
                    </li>
                     <li class="list-group-item">
                         Time Actual: <span class="badge text-bg-info">{{ $ticket->notes->where('hide', 0)->sum('hours') }} Hrs</span>
                     </li>
                </ul>
            </div>

            {{-- Timeline Card --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-body-secondary">
                    Timeline
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>Created:</strong> {{ date('M jS, Y g:ia', strtotime($ticket->created_at)) }}
                    </li>
                    <li class="list-group-item">
                        <strong>Updated:</strong> {{ date('M jS, Y g:ia', strtotime($ticket->updated_at)) }}
                    </li>
                    @if($ticket->closed_at)
                        <li class="list-group-item">
                            <strong>Closed:</strong> {{ date('M jS, Y g:ia', strtotime($ticket->closed_at)) }}
                        </li>
                    @endif
                </ul>
            </div>

            {{-- Watchers & Activity Card --}}
            @if($ticket->watchers->isNotEmpty() || count($ticketViews) > 0)
            <div class="card shadow-sm">
                <div class="card-header bg-body-secondary">
                    Watchers & Activity
                </div>
                <ul class="list-group list-group-flush">
                    @foreach ($ticket->watchers as $watcher)
                        <li class="list-group-item">
                            <i class="fas fa-eye me-2"></i>
                            <a href="mailto:{{ $watcher->user->email }}?subject=Ticket #{{ $ticket->id }}" class="text-decoration-none">{{ $watcher->user->name }}</a>
                        </li>
                    @endforeach
                    @foreach ($ticketViews as $view)
                        <li class="list-group-item text-muted small">
                            <i class="fas fa-user me-2"></i>
                            {{ $view->user->name }} - {{ \Carbon\Carbon::createFromTimeStamp(strtotime($view->viewed_at))->diffForHumans() }}
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>

    {{-- Modal for Story Points (Replaced old B3 modal structure) --}}
    <div class="modal fade" id="estimateModal" tabindex="-1" aria-labelledby="estimateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/tickets/estimate/{{ $ticket->id }}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="estimateModalLabel">Estimate Story Points</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @php
                            $estimates = [
                              0 => "No Effort",
                              1 => "XS (Extra Small), Dachshund, Kid Hot Chocolate, One",
                              2 => "Somewhere between XS and S",
                              3 => "S (Small), Terrier, Tall Late, Cookie",  
                              5 => "M (Medium), Labrador, Grande Mocha, Cheeseburger",
                              8 => "L (Large), Saint Bernard, Vente Iced Late, Cheeseburge with Fries and Soda",
                              13 => "Somewhere between L and XL",
                              21 => "XL (Extra Large), Great Dane, Trenta Mocha Frap, 5 Course Meal"
                            ];
                        @endphp
                        @foreach($estimates as $est => $label)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="storypoints" id="storypoints_{{ $est }}" value="{{ $est }}" @if($est == 0) checked @endif>
                                <label class="form-check-label" for="storypoints_{{ $est }}">
                                    <strong>{{ $est }}</strong> - {{ $label }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Estimate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
<script>
    // --- Hide Note (Vanilla JS AJAX) ---
    window.hideNote = function(noteid) {
        const noteElement = document.getElementById('note_' + noteid);
        if (!noteElement) return;

        fetch('/notes/hide/' + noteid, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
        })
            .then(response => {
                if (response.ok) {
                    noteElement.style.transition = 'opacity 0.5s ease-out, height 0.5s ease-out';
                    noteElement.style.opacity = '0';
                    noteElement.style.height = noteElement.offsetHeight + 'px';

                    setTimeout(() => {
                        noteElement.style.height = '0';
                        noteElement.style.margin = '0';
                    }, 100);

                    setTimeout(() => {
                        noteElement.remove();
                    }, 600);
                    
                } else {
                    console.error('Failed to hide note');
                }
            })
            .catch(error => console.error('Error hiding note:', error));
    }

    // --- Apply responsive class to images in description (Vanilla JS) ---
    document.querySelectorAll('.ticket-description-content img').forEach(img => {
        img.classList.add('img-fluid');
    });
</script>
<script type="module">
    document.addEventListener('DOMContentLoaded', async function() {
        const Quill = await window.loadQuill();
        
        const quillToolbarOptions = [
            ['bold', 'italic', 'underline', 'strike'], 
            ['blockquote', 'code-block'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link', 'image'],
            ['clean']
        ];

        const quill = new Quill('#note-editor-container', {
            modules: { toolbar: quillToolbarOptions },
            theme: 'snow',
            placeholder: 'Add a note or status update...'
        });
        
        const noteForm = document.getElementById('note-update-form');
        const hiddenInput = document.getElementById('note_body_hidden');

        noteForm.addEventListener('submit', function() {
            hiddenInput.value = quill.root.innerHTML;
        });
    });
</script>
@endsection
