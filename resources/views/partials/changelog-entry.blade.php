{{-- Changelog Entry — compact, distinct from comments --}}
<div class="changelog-entry d-flex align-items-start gap-2 text-muted small p-2" id="note_{{ $note->id }}">
    <i class="fas fa-history mt-1"></i>
    <div>
        <strong><a href="/users/{{ $note->user->id }}" class="text-decoration-none text-reset">{{ $note->user->name }}</a></strong> changed ticket
        <span class="ms-1">{{ $note->created_at->diffForHumans() }}</span>
        <div class="mt-1">{!! clean($note->body) !!}</div>
    </div>
</div>
