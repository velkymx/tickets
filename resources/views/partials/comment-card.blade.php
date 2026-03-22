{{-- Comment Card --}}
@php
    $signalStyles = [
        'decision' => 'border-start border-4 border-success',
        'blocker'  => 'border-start border-4 border-danger',
        'update'   => 'border-start border-4 border-info',
        'action'   => 'border-start border-4 border-warning',
    ];
    $signalBadges = [
        'decision' => ['text' => 'Decision', 'class' => 'text-bg-success'],
        'blocker'  => ['text' => 'Blocker', 'class' => 'text-bg-danger'],
        'update'   => ['text' => 'Update', 'class' => 'text-bg-info'],
        'action'   => ['text' => 'Action', 'class' => 'text-bg-warning'],
    ];
    $cardClass = $signalStyles[$note->notetype] ?? '';
    $isResolved = $note->isResolved();
@endphp

<div class="card mb-0 {{ $cardClass }}" id="note_{{ $note->id }}">
    {{-- Header --}}
    <div class="card-header bg-body-secondary d-flex justify-content-between align-items-center py-2">
        <div class="d-flex align-items-center gap-2">
            <x-avatar :user="$note->user" :size="32" />
            <strong><a href="/users/{{ $note->user->id }}" class="text-decoration-none">{{ $note->user->name }}</a></strong>
            @if(isset($signalBadges[$note->notetype]))
                <span class="badge {{ $signalBadges[$note->notetype]['class'] }} badge-sm">{{ $signalBadges[$note->notetype]['text'] }}</span>
            @endif
            @if($isResolved)
                <span class="badge text-bg-secondary">Resolved</span>
            @endif
            @if($note->hours > 0)
                <span class="badge text-bg-info">{{ $note->hours }}h</span>
            @endif
            <span class="text-muted small">{{ $note->created_at->diffForHumans() }}</span>
        </div>

        {{-- Kebab Menu --}}
        <div class="kebab-menu dropdown">
            <button class="btn btn-sm btn-link text-muted p-0" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Note actions">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @if($note->user_id === auth()->id())
                    @if($note->notetype !== 'decision')
                        <li><button class="dropdown-item" data-action="edit" data-note-id="{{ $note->id }}">Edit</button></li>
                    @endif
                @endif
                <li>
                    <form method="POST" action="/notes/{{ $note->id }}/pin">
                        @csrf
                        <button type="submit" class="dropdown-item">{{ $note->pinned ? 'Unpin' : 'Pin' }}</button>
                    </form>
                </li>
                @if(!$isResolved && $note->replies && $note->replies->count() > 0)
                    <li><button class="dropdown-item" data-action="resolve" data-note-id="{{ $note->id }}">Resolve</button></li>
                @endif
                <li>
                    <button class="dropdown-item text-danger" onclick="hideNote('{{ $note->id }}')">Hide</button>
                </li>
                @if($note->notetype === 'message')
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="/notes/{{ $note->id }}/promote" onsubmit="return confirm('This will surface in Ticket Pulse. All team members will see it.');">
                            @csrf
                            <input type="hidden" name="type" value="decision">
                            <button type="submit" class="dropdown-item">Promote to Decision</button>
                        </form>
                    </li>
                    <li>
                        <form method="POST" action="/notes/{{ $note->id }}/promote" onsubmit="return confirm('This will surface in Ticket Pulse. All team members will see it.');">
                            @csrf
                            <input type="hidden" name="type" value="blocker">
                            <button type="submit" class="dropdown-item">Promote to Blocker</button>
                        </form>
                    </li>
                @endif
            </ul>
        </div>
    </div>

    {{-- Body --}}
    <div class="card-body py-2">
        @if($note->body_markdown)
            {!! $note->body_markdown !!}
        @else
            {!! clean($note->body) !!}
        @endif

        {{-- Attachments --}}
        @if($note->attachments && $note->attachments->isNotEmpty())
            <div class="mt-2 d-flex flex-wrap gap-2">
                @foreach($note->attachments as $attachment)
                    @if($attachment->is_image)
                        <a href="{{ $attachment->url }}" target="_blank">
                            <img src="{{ $attachment->url }}" alt="{{ $attachment->filename }}" class="rounded border" style="max-height: 120px;">
                        </a>
                    @else
                        <a href="{{ $attachment->url }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                            <i class="fas fa-paperclip"></i> {{ $attachment->filename }}
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Footer: Reactions + Edited --}}
    <div class="card-footer bg-transparent border-0 py-1 d-flex align-items-center gap-2 small">
        @include('partials.reaction-bar', ['note' => $note])

        {{-- Edited indicator --}}
        @if($note->isEdited())
            <span class="text-muted ms-auto">Edited by {{ $note->user->name }} on {{ $note->edited_at->format('M j, Y') }}</span>
        @endif
    </div>

    {{-- Replies --}}
    @if($note->replies && $note->replies->count() > 0)
        @include('partials.replies-section', ['note' => $note])
    @endif

    {{-- Inline Reply Composer --}}
    <div class="reply-composer border-top px-3 py-2">
        <form method="POST" action="/notes/reply" class="d-flex gap-2 align-items-start reply-form" data-parent-id="{{ $note->id }}">
            @csrf
            <input type="hidden" name="ticket_id" value="{{ $note->ticket_id }}">
            <input type="hidden" name="parent_id" value="{{ $note->id }}">
            <textarea name="body" class="form-control form-control-sm" rows="1" placeholder="Reply..." style="resize: none;"></textarea>
            <button type="submit" class="btn btn-sm btn-outline-primary">Reply</button>
        </form>
    </div>
</div>
