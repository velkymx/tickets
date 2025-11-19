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
            <a href="/tickets/claim/{{ $ticket->id }}" class="btn btn-warning btn-sm">Claim Ticket</a>
        </div>
    @endif

    <div class="row">
        {{-- Left Column (Ticket Body, Notes, Update Form) --}}
        <div class="col-lg-8">
            <h2 class="mb-3">
                <i class="{{ $ticket->type->icon }}" title="{{ $ticket->type->name }}"></i> 
                {{ $ticket->subject }}
            </h2>

            <div id="ticket_body">
                @if($ticket->description)
                    <hr>
                    <div class="ticket-description-content">
                        {!! html_entity_decode($ticket->description) !!}
                    </div>
                @else
                    {{-- Replaced panel panel-default with card --}}
                    <div class="card card-body text-muted">
                        No Ticket Body Provided
                    </div>
                @endif
                <div class="my-5"></div>
            </div>

            {{-- Custom Alert for JS Actions (No longer jQuery-based) --}}
            <div class="alert alert-info alert-dismissible fade" role="alert" id="js-alert" style="display:none">
                <div id="js-alert-message"></div>
                <button type="button" class="btn-close" aria-label="Close" id="js-alert-close"></button>
            </div>

            {{-- Bootstrap 5 Nav Tabs --}}
            <ul class="nav nav-tabs mb-3" id="ticketTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="messages-tab" data-bs-toggle="tab" data-bs-target="#messages" type="button" role="tab" aria-controls="messages" aria-selected="true">
                        Notes ({{ $ticket->notes()->where('hide', '0')->where('notetype', 'message')->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="changelog-tab" data-bs-toggle="tab" data-bs-target="#changelog" type="button" role="tab" aria-controls="changelog" aria-selected="false">
                        Changelog ({{ $ticket->notes()->where('hide', '0')->where('notetype', 'changelog')->count() }})
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="ticketTabsContent">
                
                {{-- Messages Tab Panel --}}
                <div class="tab-pane fade show active" id="messages" role="tabpanel" aria-labelledby="messages-tab">
                    @php
                        $messageNotes = $ticket->notes()->where('hide','0')->where('notetype','message')->get();
                    @endphp
                    @if ($messageNotes->isEmpty())
                        <div class="card card-body text-muted">No Notes Found</div>
                    @else
                        @foreach ($messageNotes as $note)
                            {{-- Replaced panel panel-default with card mb-3 --}}
                            <div class="card mb-3" id="note_{{ $note->id }}">
                                <div class="card-header bg-light">
                                    <strong><a href="/users/{{ $note->user->id }}">{{ $note->user->name }}</a></strong> | posted {{ date('M jS, Y g:ia', strtotime($note->created_at)) }}
                                    <button onclick="hideNote('{{ $note->id }}');" class="btn btn-outline-danger btn-sm float-end">Remove</button>
                                </div>
                                <div class="card-body">
                                    {!! html_entity_decode($note->body) !!}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Changelog Tab Panel --}}
                <div class="tab-pane fade" id="changelog" role="tabpanel" aria-labelledby="changelog-tab">
                    @php
                        $changelogNotes = $ticket->notes()->where('hide','0')->where('notetype','changelog')->get();
                    @endphp
                    @if ($changelogNotes->isEmpty())
                        <div class="card card-body text-muted">No Changelog Entries Found</div>
                    @else
                        @foreach ($changelogNotes as $change)
                            <div class="card mb-3" id="note_{{ $change->id }}">
                                <div class="card-header bg-light">
                                    <strong><a href="/users/{{ $change->user->id }}">{{ $change->user->name }}</a></strong> changed ticket {{ date('M jS, Y g:ia', strtotime($change->created_at)) }}
                                    <button onclick="hideNote('{{ $change->id }}');" class="btn btn-outline-danger btn-sm float-end">Remove</button>
                                </div>
                                <div class="card-body">
                                    {!! html_entity_decode($change->body) !!}
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
                    <div id="note-editor-container" style="height: 200px;"></div>
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
                <div class="col-4">
                    <button type="button" class="btn btn-block btn-info w-100" id="watch-ticket">
                        <i class="fas fa-eye"></i> Watch
                    </button>
                </div>
                <div class="col-4">
                    <a href="/tickets/edit/{{ $ticket->id }}" class="btn btn-block btn-secondary w-100">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
                <div class="col-4">
                    <a href="/tickets/clone/{{ $ticket->id }}" class="btn btn-block btn-secondary w-100">
                        <i class="fas fa-copy"></i> Clone
                    </a>
                </div>
            </div>

            {{-- Ticket Details Card (Replaced panel panel-default) --}}
            <div class="card">  
                <div class="card-header">Ticket Details</div>
                <table class="table table-sm mb-0">
                    <tbody>
                        @if ($ticket->due_at) {{-- Check if due_at is set --}}
                        <tr>
                            <td>Due</td>
                            <td>{{ date('M jS, Y', strtotime($ticket->due_at)) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td>Importance</td>
                            <td><span class="text-{{ $ticket->importance->class }}" title="{{ $ticket->importance->name }}"><i class="{{ $ticket->importance->icon }}"></i> {{ $ticket->importance->name }}</span></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>{{ $ticket->status->name }}</td>
                        </tr>
                        <tr>
                            <td>Assignee</td>
                            <td><a href="/users/{{ $ticket->assignee->id }}">{{ $ticket->assignee->name }}</a></td>
                        </tr>
                        <tr>
                            <td>Type</td>
                            <td><i class="{{ $ticket->type->icon }}" title="{{ $ticket->type->name }}"></i> {{ $ticket->type->name }}</td>
                        </tr>
                        <tr>
                            <td>Owner</td>
                            <td><a href="/users/{{ $ticket->user->id }}">{{ $ticket->user->name }}</a></td>
                        </tr>
                        <tr>
                            <td>Milestone</td>
                            <td><a href="/milestone/show/{{ $ticket->milestone->id }}">{{ $ticket->milestone->name }}</a></td>
                        </tr>
                        <tr>
                            <td>Project</td>
                            <td><a href="/projects/show/{{ $ticket->project->id }}">{{ $ticket->project->name }}</a></td>
                        </tr>
                        <tr>
                            <td>Story Points</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary float-end" data-bs-toggle="modal" data-bs-target="#estimateModal">
                                    Estimate
                                </button>
                                
                                <strong class="me-2">{{ $ticket->storypoints }} Points</strong>
                                
                                @if($ticket->userstorypoints->isNotEmpty())
                                    <ul class="list-group list-group-flush mt-2">
                                        @foreach($ticket->userstorypoints as $usp)
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                {{ $usp->storypoints }} points 
                                                <span class="badge bg-secondary">{{ $usp->user->name }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Time Estimate</td>
                            <td>{{ $ticket->estimate }} hours</td>
                        </tr>
                        <tr>
                            <td>Time Actual</td>
                            <td>{{ $ticket->notes()->where('hide', 0)->sum('hours') }} hours</td>
                        </tr>
                        <tr>
                            <td>Created</td>
                            <td>{{ date('M jS, Y g:ia', strtotime($ticket->created_at)) }}</td>
                        </tr>
                        <tr>
                            <td>Updated</td>
                            <td>{{ date('M jS, Y g:ia', strtotime($ticket->updated_at)) }}</td>
                        </tr>
                        @if($ticket->closed_at)
                        <tr>
                            <td>Closed</td>
                            <td>{{ date('M jS, Y g:ia', strtotime($ticket->closed_at)) }}</td>
                        </tr>
                        @endif
                        @foreach ($ticket->watchers as $watcher)
                        <tr>
                            <td>Watcher</td>
                            <td><a href="mailto:{{ $watcher->user->email }}?subject=Ticket #{{ $ticket->id }}">{{ $watcher->user->name }}</a></td>
                        </tr>
                        @endforeach
                        {{-- Laravel/DB query logic retained, assuming DB/Carbon are available --}}
                        @foreach ($ticket->views()->select([\DB::raw('DISTINCT user_id'), \DB::raw('max(created_at) as viewed_at')])->groupBy('user_id')->get() as $view)
                        <tr>
                            <td>User View</td>
                            <td>{{ $view->user->name }} - {{ \Carbon\Carbon::createFromTimeStamp(strtotime($view->viewed_at))->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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
{{-- Quill.js CSS and JS --}}
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. Quill Initialization ---
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
        
        // --- 2. Form Submission Handler (Quill Content) ---
        const noteForm = document.getElementById('note-update-form');
        const hiddenInput = document.getElementById('note_body_hidden');

        noteForm.addEventListener('submit', function() {
            // Get the content as HTML and set it to the hidden input
            hiddenInput.value = quill.root.innerHTML;
        });

        // --- 3. Hide Note (Vanilla JS AJAX) ---
        window.hideNote = function(noteid) {
            const noteElement = document.getElementById('note_' + noteid);
            if (!noteElement) return;

            // Simple AJAX using fetch (replace jQuery.load)
            fetch('/notes/hide/' + noteid)
                .then(response => {
                    if (response.ok) {
                        noteElement.style.transition = 'opacity 0.5s ease-out, height 0.5s ease-out';
                        noteElement.style.opacity = '0';
                        noteElement.style.height = noteElement.offsetHeight + 'px'; // Set height before collapsing

                        // Wait for transition, then collapse and remove
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

        // --- 4. Watch Ticket (Vanilla JS AJAX) ---
        const watchButton = document.getElementById('watch-ticket');
        const alertDiv = document.getElementById('js-alert');
        const alertMessage = document.getElementById('js-alert-message');
        const ticketId = {{ $ticket->id }}; // Directly use the Blade variable

        watchButton.addEventListener('click', function() {
            fetch('/users/watch/' + ticketId)
                .then(response => response.text())
                .then(data => {
                    alertMessage.innerHTML = data;
                    alertDiv.style.display = 'block';
                    alertDiv.classList.add('show');
                })
                .catch(error => {
                    alertMessage.textContent = 'Error watching ticket.';
                    alertDiv.style.display = 'block';
                    alertDiv.classList.add('show');
                    console.error('Watch error:', error);
                });
        });

        // --- 5. Custom Alert Close (Vanilla JS) ---
        document.getElementById('js-alert-close').addEventListener('click', function() {
            alertDiv.classList.remove('show');
            // Give time for fade transition before hiding display
            setTimeout(() => {
                alertDiv.style.display = 'none';
            }, 150);
        });

        // --- 6. Apply responsive class to images in description (Vanilla JS) ---
        document.querySelectorAll('.ticket-description-content img').forEach(img => {
            img.classList.add('img-fluid'); // Bootstrap 5 equivalent of img-responsive
        });
        
    });
</script>
@endsection