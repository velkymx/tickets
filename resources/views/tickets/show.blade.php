@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id)

@section('content')

    <div data-keyboard-shortcuts="true">

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

    @if($ticket->assignee->name == 'Unassigned')
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
        <div class="col-lg-8">
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
            <div class="markdown-composer" data-users="@json($allUsers)">
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
                            <div class="slash-autocomplete dropdown-menu position-absolute d-none" data-commands="@json($slashCommands)">
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

            {{-- Ticket Pulse (Real-Time Decision Log) --}}
            <x-ticket-pulse :ticket="$ticket" :pulse="$pulse->toArray()" />

            {{-- Ticket Details Card --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-body-secondary">
                    Ticket Details
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>Status:</strong> <span class="badge text-bg-secondary">{{ $ticket->status->name }}</span>
                    </li>
                    <li class="list-group-item">
                        <strong>Importance:</strong>
                        <span class="text-{{ $ticket->importance->class }}">
                            <i class="{{ $ticket->importance->icon }}"></i> {{ $ticket->importance->name }}
                        </span>
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
                    <li class="list-group-item">
                        <strong>Project:</strong>
                        <a href="/projects/show/{{ $ticket->project->id }}" class="text-decoration-none">{{ $ticket->project->name }}</a>
                    </li>
                    <li class="list-group-item">
                        <strong>Milestone:</strong>
                        <a href="/milestone/show/{{ $ticket->milestone->id }}" class="text-decoration-none">{{ $ticket->milestone->name }}</a>
                    </li>
                    @if ($ticket->due_at)
                        <li class="list-group-item">
                            <strong>Due:</strong> {{ date('M jS, Y', strtotime($ticket->due_at)) }}
                        </li>
                    @endif
                    <li class="list-group-item text-muted small">
                        Created {{ date('M jS, Y g:ia', strtotime($ticket->created_at)) }}
                        &middot; Updated {{ date('M jS, Y g:ia', strtotime($ticket->updated_at)) }}
                        @if($ticket->closed_at)
                            &middot; Closed {{ date('M jS, Y g:ia', strtotime($ticket->closed_at)) }}
                        @endif
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

    {{-- Composer Help Modal --}}
    @include('partials.composer-help')

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

    </div>{{-- /data-keyboard-shortcuts --}}

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

    // --- Global keyboard shortcuts ---
    document.addEventListener('keydown', function(e) {
        // Ignore when typing in inputs/textareas
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            if (e.key === 'Escape') {
                e.target.blur();
            }
            return;
        }

        switch(e.key) {
            case '?':
                e.preventDefault();
                new bootstrap.Modal(document.getElementById('composerHelpModal')).show();
                break;
            case 'r':
                e.preventDefault();
                document.getElementById('note-textarea')?.focus();
                break;
            case '/':
                e.preventDefault();
                const textarea = document.getElementById('note-textarea');
                if (textarea) {
                    textarea.focus();
                    textarea.value = '/';
                }
                break;
            case 'j':
                e.preventDefault();
                navigateComments(1);
                break;
            case 'k':
                e.preventDefault();
                navigateComments(-1);
                break;
        }
    });

    let currentCommentIndex = -1;
    function navigateComments(direction) {
        const entries = document.querySelectorAll('.timeline-entry');
        if (!entries.length) return;
        currentCommentIndex = Math.max(0, Math.min(entries.length - 1, currentCommentIndex + direction));
        entries[currentCommentIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
        entries[currentCommentIndex].classList.add('ring');
        setTimeout(() => entries[currentCommentIndex].classList.remove('ring'), 1000);
    }

    document.addEventListener('submit', function(e) {
        const form = e.target.closest('.reaction-toggle-form');
        if (!form) {
            return;
        }

        e.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        if (submitButton) {
            submitButton.disabled = true;
        }

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        })
            .then(async response => {
                if (!response.ok) {
                    throw new Error('Failed to toggle reaction');
                }

                return response.json();
            })
            .then(data => {
                const currentBar = form.closest('.reaction-bar');

                if (currentBar && data.html) {
                    currentBar.outerHTML = data.html;
                }
            })
            .catch(error => {
                console.error('Error toggling reaction:', error);
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });
    });

    document.addEventListener('submit', function(e) {
        const form = e.target.closest('.reply-form');
        if (!form) {
            return;
        }

        e.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        const textarea = form.querySelector('textarea[name="body"]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        if (submitButton) {
            submitButton.disabled = true;
        }

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        })
            .then(async response => {
                const data = await response.json();

                if (!response.ok) {
                    throw new Error((data.errors && Object.values(data.errors).flat()[0]) || 'Failed to add reply');
                }

                return data;
            })
            .then(data => {
                const noteCard = form.closest('.card');
                const composer = form.closest('.reply-composer');
                const existingReplies = noteCard?.querySelector('.replies-section');

                if (existingReplies && data.replies_html) {
                    existingReplies.outerHTML = data.replies_html;
                } else if (composer && data.replies_html) {
                    composer.insertAdjacentHTML('beforebegin', data.replies_html);
                }

                form.reset();
                if (textarea) {
                    textarea.focus();
                }
            })
            .catch(error => {
                console.error('Error adding reply:', error);
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });
    });
</script>
<script>
    // --- Markdown Composer: Cmd+Enter to submit ---
    document.getElementById('note-textarea')?.addEventListener('keydown', function(e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
            e.preventDefault();
            this.closest('form').submit();
        }
    });

    // --- Smart paste detection ---
    document.getElementById('note-textarea')?.addEventListener('paste', function(e) {
        const text = (e.clipboardData || window.clipboardData).getData('text');
        if (!text) return;

        // Stack trace detection
        const stackTracePattern = /^\s*(at |Traceback|Exception|Error:|Fatal|#\d+ |\.php:\d+|\.js:\d+|\.py:\d+)/m;
        if (stackTracePattern.test(text) && text.split('\n').length > 3) {
            e.preventDefault();
            const wrapped = '```\n' + text.trim() + '\n```';
            insertAtCursor(this, wrapped);
            return;
        }

        // JSON detection
        try {
            const parsed = JSON.parse(text);
            if (typeof parsed === 'object') {
                e.preventDefault();
                const formatted = '```json\n' + JSON.stringify(parsed, null, 2) + '\n```';
                insertAtCursor(this, formatted);
                return;
            }
        } catch (_) {}

        // URL detection
        const urlPattern = /^https?:\/\/\S+$/;
        if (urlPattern.test(text.trim())) {
            e.preventDefault();
            insertAtCursor(this, '[' + text.trim() + '](' + text.trim() + ')');
        }
    });

    function insertAtCursor(textarea, text) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
        textarea.setSelectionRange(start + text.length, start + text.length);
    }

    // --- Signal nudge: blocker keyword detection ---
    document.getElementById('note-textarea')?.addEventListener('input', function() {
        const nudge = document.getElementById('signal-nudge');
        if (!nudge) return;
        const blockerKeywords = /\b(blocked|waiting on|depends on|can't proceed|cannot proceed)\b/i;
        if (blockerKeywords.test(this.value)) {
            nudge.textContent = 'Tip: Use /blocker to make this visible in Ticket Pulse';
            nudge.classList.remove('d-none');
        } else {
            nudge.classList.add('d-none');
        }
    });

    // --- Markdown toolbar buttons ---
    document.querySelectorAll('.markdown-composer [data-md]').forEach(btn => {
        btn.addEventListener('click', function() {
            const textarea = document.getElementById('note-textarea');
            if (!textarea) return;
            const md = this.dataset.md;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selected = textarea.value.substring(start, end);
            textarea.value = textarea.value.substring(0, start) + md + selected + md + textarea.value.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + md.length, end + md.length);
        });
    });

    // --- @Mention Autocomplete ---
    (function() {
        const textarea = document.getElementById('note-textarea');
        const composer = textarea?.closest('.markdown-composer');
        if (!textarea || !composer) return;

        const dropdown = composer.querySelector('.mention-autocomplete');
        const users = JSON.parse(composer.dataset.users || '[]');
        let mentionStart = -1;
        let activeIndex = -1;

        function buildDropdown(filter) {
            const lower = filter.toLowerCase();
            const filtered = lower === ''
                ? users
                : users.filter(u => u.name.toLowerCase().includes(lower));

            dropdown.innerHTML = '';
            activeIndex = -1;

            if (filtered.length === 0) {
                hide();
                return;
            }

            filtered.forEach((u, i) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'dropdown-item';
                btn.dataset.userId = u.id;
                btn.dataset.userName = u.name;
                btn.dataset.userTitle = u.title || '';
                btn.textContent = u.title ? `${u.name} (${u.title})` : u.name;
                btn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    selectUser(u);
                });
                dropdown.appendChild(btn);
            });

            dropdown.classList.remove('d-none');
            dropdown.classList.add('show');
            setActive(0);
        }

        function selectUser(u) {
            const token = u.title ? `@[${u.name} (${u.title})]` : `@[${u.name}]`;
            const before = textarea.value.substring(0, mentionStart);
            const after = textarea.value.substring(textarea.selectionStart);
            textarea.value = before + token + ' ' + after;
            const cursorPos = before.length + token.length + 1;
            textarea.setSelectionRange(cursorPos, cursorPos);
            hide();
            textarea.focus();
        }

        function setActive(index) {
            const items = dropdown.querySelectorAll('.dropdown-item');
            items.forEach(i => i.classList.remove('active'));
            if (index >= 0 && index < items.length) {
                activeIndex = index;
                items[index].classList.add('active');
                items[index].scrollIntoView({ block: 'nearest' });
            }
        }

        function hide() {
            dropdown.classList.add('d-none');
            dropdown.classList.remove('show');
            mentionStart = -1;
            activeIndex = -1;
        }

        textarea.addEventListener('input', function() {
            const pos = this.selectionStart;
            const text = this.value.substring(0, pos);

            const lastAt = text.lastIndexOf('@');
            if (lastAt === -1 || (lastAt > 0 && /\w/.test(text[lastAt - 1]))) {
                hide();
                return;
            }

            const afterAt = text.substring(lastAt);
            if (afterAt.includes(']')) {
                hide();
                return;
            }

            mentionStart = lastAt;
            const filter = text.substring(lastAt + 1);
            buildDropdown(filter);
        });

        textarea.addEventListener('keydown', function(e) {
            if (mentionStart === -1) return;

            const items = dropdown.querySelectorAll('.dropdown-item');
            if (items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setActive(Math.min(activeIndex + 1, items.length - 1));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setActive(Math.max(activeIndex - 1, 0));
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                const item = items[activeIndex];
                selectUser({
                    id: item.dataset.userId,
                    name: item.dataset.userName,
                    title: item.dataset.userTitle
                });
            } else if (e.key === 'Escape') {
                e.preventDefault();
                hide();
            }
        });

        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && e.target !== textarea) {
                hide();
            }
        });
    })();

    // --- Presence heartbeat (15s interval) ---
    (function() {
        const ticketId = document.querySelector('.presence-indicator')?.dataset.ticketId;
        if (!ticketId) return;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        function sendHeartbeat() {
            const composing = document.activeElement?.id === 'note-textarea';
            fetch(`/tickets/${ticketId}/presence`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ composing })
            })
            .then(r => r.json())
            .then(data => updatePresenceUI(data.viewers))
            .catch(() => {});
        }

        function updatePresenceUI(viewers) {
            const container = document.getElementById('presence-viewers');
            if (!container || !viewers) return;
            const maxShow = 3;
            let html = '<div class="d-flex" style="margin-left: -8px;">';
            const escape = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            viewers.slice(0, maxShow).forEach((v, i) => {
                const hash = v.email ? md5(v.email.toLowerCase().trim()) : '';
                const composingDot = v.composing ? '<span class="position-absolute bottom-0 end-0 badge bg-success rounded-circle" style="width:8px;height:8px;padding:0;">…</span>' : '';
                html += `<div class="position-relative" style="margin-left:-8px;z-index:${maxShow - i};" title="${escape(v.name)}">
                    <img src="https://www.gravatar.com/avatar/${hash}?s=28&d=mp" class="rounded-circle border border-2 border-white" width="28" height="28">
                    ${composingDot}
                </div>`;
            });
            if (viewers.length > maxShow) {
                html += `<div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center small" style="width:28px;height:28px;margin-left:-8px;">+${viewers.length - maxShow}</div>`;
            }
            html += '</div>';
            container.innerHTML = html;
        }

        sendHeartbeat();
        setInterval(sendHeartbeat, 15000);
    })();

    // --- New activity polling (30s interval) ---
    (function() {
        const ticketId = document.querySelector('.presence-indicator')?.dataset.ticketId;
        if (!ticketId) return;
        let lastChecked = new Date().toISOString();

        setInterval(() => {
            fetch(`/tickets/${ticketId}/presence`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                // Activity polling handled via presence endpoint for now
            })
            .catch(() => {});
        }, 30000);
    })();
</script>
@endsection
