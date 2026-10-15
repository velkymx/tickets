@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id)

@section('content')

    <div data-ticket-show data-keyboard-shortcuts="true">

    {{-- Keyboard shortcut help icon --}}
    <div class="position-fixed bottom-0 end-0 mb-3 me-3" style="z-index: 1000;">
        <button class="keyboard-icon btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="modal" data-bs-target="#composerHelpModal" title="Keyboard shortcuts (?)">
            <i class="fas fa-keyboard"></i>
        </button>
    </div>

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

    @if(! $ticket->assignee || $ticket->assignee->name == 'Unassigned')
        <div class="alert alert-warning d-flex justify-content-between align-items-center" role="alert"> 
            <span>This Ticket is currently unassigned.</span>
            <form action="/tickets/claim/{{ $ticket->id }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">Claim Ticket</button>
            </form>
        </div>
    @endif

    <div class="row">
        {{-- Left Column (Ticket Body, Notes, Update Form) --}}
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h2 class="mb-0">
                    <i class="{{ $ticket->type->icon }}" title="{{ $ticket->type->name }}" aria-hidden="true"></i>
                    {{ $ticket->subject }}
                </h2>
                {{-- Presence Indicator --}}
                <div class="presence-indicator d-flex align-items-center" data-ticket-id="{{ $ticket->id }}" id="presence-viewers">
                    {{-- Populated by JS polling --}}
                </div>
            </div>

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

            {{-- Unified Activity Timeline --}}
            @include('partials.activity-timeline')

            <hr class="mt-4 mb-4" />

            {{-- Markdown Composer --}}
            <div class="markdown-composer" data-users='@json($allUsers)'>
                <form method="POST" action="{{ url('notes') }}" id="note-update-form">
                    @csrf

                    {{-- Markdown Textarea --}}
                    <div class="mb-3">
                        <label for="note" class="form-label">Status Update and Notes</label>
                        <div class="d-flex gap-1 mb-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-md="**" title="Bold"><i class="fas fa-bold"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-md="*" title="Italic"><i class="fas fa-italic"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-md="`" title="Code"><i class="fas fa-code"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-md="link" title="Link"><i class="fas fa-link"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-md="list" title="List"><i class="fas fa-list"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-action="attach" title="Attach"><i class="fas fa-paperclip"></i></button>
                        </div>
                        <div class="position-relative">
                            <textarea name="note" id="note-textarea" class="form-control" rows="5" placeholder="Add a note or status update... (Markdown supported, / for commands, @ for mentions)">{{ old('note') }}</textarea>

                            {{-- Slash Command Autocomplete --}}
                            @php
                                $slashCommands = [
                                    ['cmd' => '/assign', 'desc' => 'Assign ticket to user'],
                                    ['cmd' => '/status', 'desc' => 'Change ticket status'],
                                    ['cmd' => '/decision', 'desc' => 'Record a decision (immutable)'],
                                    ['cmd' => '/blocker', 'desc' => 'Flag a blocker'],
                                    ['cmd' => '/action', 'desc' => 'Create an action item'],
                                    ['cmd' => '/hours', 'desc' => 'Log time'],
                                    ['cmd' => '/estimate', 'desc' => 'Set story points'],
                                    ['cmd' => '/close', 'desc' => 'Close the ticket'],
                                    ['cmd' => '/reopen', 'desc' => 'Reopen the ticket'],
                                    ['cmd' => '/pin', 'desc' => 'Pin this note'],
                                ];
                            @endphp
                            <div class="slash-autocomplete dropdown-menu position-absolute d-none" data-commands='@json($slashCommands)'>
                                @foreach($slashCommands as $cmd)
                                    <button type="button" class="dropdown-item" data-command="{{ $cmd['cmd'] }}">
                                        <code>{{ $cmd['cmd'] }}</code> <span class="text-muted small">{{ $cmd['desc'] }}</span>
                                    </button>
                                @endforeach
                            </div>

                            {{-- @Mention Autocomplete (populated by JS from data-users) --}}
                            <div class="mention-autocomplete dropdown-menu position-absolute d-none"></div>
                        </div>
                    </div>

                    {{-- Signal Nudge --}}
                    <div class="signal-nudge text-muted small d-none mb-2" id="signal-nudge"></div>

                    {{-- Action Preview Bar --}}
                    <div id="action-preview" class="alert alert-light border d-none mb-3">
                        <strong>Actions:</strong>
                        <ul id="action-preview-list" class="mb-0 small"></ul>
                    </div>

                    {{-- Status & Time (collapsible) --}}
                    <div class="status-time-section mb-3">
                        <a class="text-decoration-none small" data-bs-toggle="collapse" href="#statusTimeCollapse" role="button" aria-expanded="false">
                            Status & Time
                        </a>
                        <div class="collapse show" id="statusTimeCollapse">
                            <div class="row g-2 mt-1">
                                <div class="col">
                                    <select name="status_id" id="status_id" class="form-select form-select-sm">
                                        @foreach ($lookups['statuses'] as $id => $name)
                                            <option value="{{ $id }}" @selected($ticket->status->id == $id)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <input type="text" name="hours" id="hours" class="form-control form-control-sm" value="{{ old('hours', 0) }}" placeholder="Hours" style="width: 80px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="#" class="btn btn-sm btn-outline-secondary composer-help" data-bs-toggle="modal" data-bs-target="#composerHelpModal">? Help</a>
                        <button type="submit" class="btn btn-success">Post Update</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Right Column (Details) --}}
        <div class="col-lg-3 mt-4 mt-lg-0">
            
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

            {{-- Ticket Pulse (Real-Time Decision Log) --}}
            <x-ticket-pulse :ticket="$ticket" :pulse="$pulse->toArray()" />

            {{-- Ticket Details --}}
            <section class="mb-4">
                <h6 class="text-uppercase text-body-secondary fw-semibold small mb-2 pb-1 border-bottom">
                    Ticket Details
                </h6>
                <dl class="mb-0 small">
                    <div class="d-flex justify-content-between align-items-center gap-3 py-1">
                        <dt class="fw-normal text-body-secondary">Status</dt>
                        <dd class="mb-0 text-end"><span class="badge text-bg-secondary">{{ $ticket->status->name }}</span></dd>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-3 py-1">
                        <dt class="fw-normal text-body-secondary">Importance</dt>
                        <dd class="mb-0 text-end text-{{ $ticket->importance->class }}">
                            <i class="{{ $ticket->importance->icon }}"></i> {{ $ticket->importance->name }}
                        </dd>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-3 py-1">
                        <dt class="fw-normal text-body-secondary">Type</dt>
                        <dd class="mb-0 text-end"><i class="{{ $ticket->type->icon }}"></i> {{ $ticket->type->name }}</dd>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-3 py-1">
                        <dt class="fw-normal text-body-secondary">Assignee</dt>
                        <dd class="mb-0 text-end">
                            @if ($ticket->assignee)
                                <a href="/users/{{ $ticket->assignee->id }}" class="text-decoration-none">{{ $ticket->assignee->name }}</a>
                            @else
                                <span class="text-body-secondary fst-italic">Unassigned</span>
                            @endif
                        </dd>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-3 py-1">
                        <dt class="fw-normal text-body-secondary">Owner</dt>
                        <dd class="mb-0 text-end"><a href="/users/{{ $ticket->user->id }}" class="text-decoration-none">{{ $ticket->user->name }}</a></dd>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-3 py-1">
                        <dt class="fw-normal text-body-secondary">Project</dt>
                        <dd class="mb-0 text-end"><a href="/projects/show/{{ $ticket->project->id }}" class="text-decoration-none">{{ $ticket->project->name }}</a></dd>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-3 py-1">
                        <dt class="fw-normal text-body-secondary">Milestone</dt>
                        <dd class="mb-0 text-end"><a href="/milestone/show/{{ $ticket->milestone->id }}" class="text-decoration-none">{{ $ticket->milestone->name }}</a></dd>
                    </div>
                    @if ($ticket->due_at)
                        <div class="d-flex justify-content-between align-items-center gap-3 py-1">
                            <dt class="fw-normal text-body-secondary">Due</dt>
                            <dd class="mb-0 text-end">{{ date('M jS, Y', strtotime($ticket->due_at)) }}</dd>
                        </div>
                    @endif
                </dl>
                <p class="text-body-secondary mt-2 mb-0" style="font-size: .75rem;">
                    Created {{ date('M jS, Y g:ia', strtotime($ticket->created_at)) }}
                    &middot; Updated {{ date('M jS, Y g:ia', strtotime($ticket->updated_at)) }}
                    @if($ticket->closed_at)
                        &middot; Closed {{ date('M jS, Y g:ia', strtotime($ticket->closed_at)) }}
                    @endif
                </p>
            </section>

            {{-- Effort & Estimates --}}
            <section class="mb-4">
                <h6 class="text-uppercase text-body-secondary fw-semibold small mb-2 pb-1 border-bottom d-flex justify-content-between align-items-center">
                    <span>Effort &amp; Estimates</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-bs-toggle="modal" data-bs-target="#estimateModal">
                        Estimate
                    </button>
                </h6>
                <dl class="mb-0 small">
                    <div class="d-flex justify-content-between align-items-center gap-3 py-1">
                        <dt class="fw-normal text-body-secondary">Story Points</dt>
                        <dd class="mb-0 text-end"><span class="badge text-bg-primary">{{ $ticket->storypoints }} Points</span></dd>
                    </div>
                    @foreach($ticket->estimates as $usp)
                        <div class="d-flex justify-content-between align-items-center gap-3 py-1">
                            <dt class="fw-normal text-body-secondary">
                                <a href="/users/{{ $usp->user->id }}" class="text-decoration-none text-reset">{{ $usp->user->name }}</a>
                            </dt>
                            <dd class="mb-0 text-end">{{ $usp->storypoints }} points</dd>
                        </div>
                    @endforeach
                    <div class="d-flex justify-content-between align-items-center gap-3 py-1">
                        <dt class="fw-normal text-body-secondary">Time Estimate</dt>
                        <dd class="mb-0 text-end"><span class="badge text-bg-primary">{{ $ticket->estimate }} Hrs</span></dd>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-3 py-1">
                        <dt class="fw-normal text-body-secondary">Time Actual</dt>
                        <dd class="mb-0 text-end"><span class="badge text-bg-info">{{ $ticket->notes->where('hide', 0)->sum('hours') }} Hrs</span></dd>
                    </div>
                </dl>
            </section>

            {{-- Watchers & Activity --}}
            @if($ticket->watchers->isNotEmpty() || count($ticketViews) > 0)
                <section>
                    <h6 class="text-uppercase text-body-secondary fw-semibold small mb-2 pb-1 border-bottom">
                        Watchers &amp; Activity
                    </h6>
                    <ul class="list-unstyled mb-0 small">
                        @foreach ($ticket->watchers as $watcher)
                            <li class="py-1">
                                <i class="fas fa-eye text-body-secondary me-2"></i>
                                <a href="/users/{{ $watcher->user->id }}" class="text-decoration-none">{{ $watcher->user->name }}</a>
                            </li>
                        @endforeach
                        @foreach ($ticketViews as $view)
                            <li class="py-1 text-body-secondary">
                                <i class="fas fa-user me-2"></i>
                                <a href="/users/{{ $view->user->id }}" class="text-decoration-none">{{ $view->user->name }}</a> &mdash; {{ \Carbon\Carbon::createFromTimeStamp(strtotime($view->viewed_at))->diffForHumans() }}
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>

    {{-- Composer Help Modal --}}
    @include('partials.composer-help')

    {{-- Modal for Story Points (Replaced old B3 modal structure) --}}
    <style>
        .estimate-card { transition: transform .12s ease, box-shadow .12s ease; }
        .estimate-card:hover { transform: translateY(-3px); }
        .btn-check:checked + .estimate-card { transform: translateY(-4px); box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15); }
    </style>
    <div class="modal fade" id="estimateModal" tabindex="-1" aria-labelledby="estimateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
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
                              0  => ['tag' => '0',    'name' => 'No Effort',      'fun' => '😴 Nothing to do'],
                              1  => ['tag' => 'XS',   'name' => 'Extra Small',    'fun' => '🐕 Dachshund · kid hot chocolate'],
                              2  => ['tag' => 'XS–S', 'name' => 'Between XS & S',  'fun' => '📏 A touch bigger than XS'],
                              3  => ['tag' => 'S',    'name' => 'Small',          'fun' => '🐕 Terrier · tall latte · cookie'],
                              5  => ['tag' => 'M',    'name' => 'Medium',         'fun' => '🐕 Labrador · grande mocha · cheeseburger'],
                              8  => ['tag' => 'L',    'name' => 'Large',          'fun' => '🐕 Saint Bernard · venti iced latte · burger, fries & soda'],
                              13 => ['tag' => 'L–XL', 'name' => 'Between L & XL',  'fun' => '📏 Bigger than a Large'],
                              21 => ['tag' => 'XL',   'name' => 'Extra Large',    'fun' => '🐕 Great Dane · trenta frap · 5-course meal'],
                            ];
                        @endphp

                        <p class="text-body-secondary small mb-3">Pick a card — how big is this one?</p>

                        <div class="row row-cols-2 row-cols-sm-4 g-2">
                            @foreach($estimates as $est => $meta)
                                <div class="col">
                                    <input type="radio" class="btn-check" name="storypoints"
                                           id="storypoints_{{ $est }}" value="{{ $est }}" autocomplete="off"
                                           @checked($est == $ticket->storypoints)>
                                    <label class="btn btn-outline-primary w-100 h-100 estimate-card d-flex flex-column align-items-center justify-content-center text-center py-3"
                                           for="storypoints_{{ $est }}" title="{{ $meta['fun'] }}">
                                        <span class="display-6 fw-bold lh-1">{{ $est }}</span>
                                        <span class="badge rounded-pill text-bg-light mt-2">{{ $meta['tag'] }}</span>
                                        <span class="small text-body-secondary mt-1">{{ $meta['name'] }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Estimate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    </div>{{-- /data-keyboard-shortcuts --}}

@endsection
