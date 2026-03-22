{{-- Changelog Entry (stub — will be fully implemented in 7.3) --}}
<div class="d-flex align-items-start gap-2 text-muted small" id="note_{{ $note->id }}">
    <i class="fas fa-history mt-1"></i>
    <div>
        <strong>{{ $note->user->name }}</strong> changed ticket
        <span class="ms-1">{{ $note->created_at->diffForHumans() }}</span>
        <div>{!! clean($note->body) !!}</div>
    </div>
</div>
