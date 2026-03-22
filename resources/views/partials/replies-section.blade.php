<div class="replies-section border-top ms-4 me-2 mb-2">
    @foreach($note->replies as $reply)
        <div class="d-flex gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2">
                    <strong class="small"><a href="/users/{{ $reply->user->id }}" class="text-decoration-none">{{ $reply->user->name }}</a></strong>
                    <span class="text-muted small">{{ $reply->created_at->diffForHumans() }}</span>
                </div>
                <div class="small">
                    @if($reply->body_markdown)
                        {!! clean($reply->body_markdown) !!}
                    @else
                        {!! clean($reply->body) !!}
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
