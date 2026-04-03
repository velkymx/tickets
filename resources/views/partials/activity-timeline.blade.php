{{-- Unified Activity Timeline --}}
<div id="activity-timeline">

    {{-- Filter Buttons --}}
    <div class="d-flex gap-2 mb-3 timeline-filter" role="group" aria-label="Timeline filters">
        <button type="button" class="btn btn-sm btn-outline-secondary active" data-filter="all">All</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="message">Comments</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="decision">Decisions</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="blocker">Blockers</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="changelog">Activity</button>
    </div>

    {{-- Pinned Notes Section --}}
    @if($pinnedNotes->isNotEmpty())
        <div class="pinned-notes-section mb-3">
            @foreach($pinnedNotes as $pinned)
                <div class="alert alert-light border-start border-4 border-primary d-flex align-items-start gap-2 py-2" role="alert">
                    <i class="fas fa-thumbtack text-primary mt-1"></i>
                    <div>
                        <strong>{{ $pinned->user->name }}:</strong>
                        {!! clean($pinned->body) !!}
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Timeline Entries --}}
    @php
        $visibleNotes = $ticket->notes->whereNull('parent_id');
        $hasShownDivider = false;
    @endphp

    @if($visibleNotes->isEmpty())
        <div class="card card-body text-muted">No activity yet.</div>
    @else
        @foreach($visibleNotes as $note)
            @if(!$hasShownDivider && isset($lastViewedAt) && $note->created_at->gt($lastViewedAt))
                <div class="unread-divider d-flex align-items-center my-3">
                    <hr class="flex-grow-1">
                    <span class="px-3 text-danger small fw-semibold">New since your last visit</span>
                    <hr class="flex-grow-1">
                </div>
                @php $hasShownDivider = true; @endphp
            @endif

            <div class="timeline-entry mb-3" data-entry-type="{{ $note->notetype }}" data-note-id="{{ $note->id }}">
                @if($note->notetype === 'changelog')
                    @include('partials.changelog-entry', ['note' => $note])
                @else
                    @include('partials.comment-card', ['note' => $note])
                @endif
            </div>
        @endforeach
    @endif

    {{-- Quick Navigation --}}
    <div class="d-flex gap-2 mt-3 small">
        <a href="#activity-timeline" class="btn btn-sm btn-outline-secondary">Latest</a>
        @if($pinnedNotes->isNotEmpty())
            <a href="#activity-timeline .pinned-notes-section" class="btn btn-sm btn-outline-secondary">Pinned</a>
        @endif
    </div>
</div>
