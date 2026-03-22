{{-- Comment Card (stub — will be fully implemented in 7.2) --}}
<div class="card" id="note_{{ $note->id }}">
    <div class="card-header bg-body-secondary d-flex justify-content-between align-items-center">
        <div>
            <strong><a href="/users/{{ $note->user->id }}">{{ $note->user->name }}</a></strong>
            <span class="text-muted small ms-2">{{ $note->created_at->diffForHumans() }}</span>
        </div>
    </div>
    <div class="card-body">
        {!! clean($note->body) !!}
    </div>
</div>
