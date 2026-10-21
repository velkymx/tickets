@props([
    'requests',
    'title' => 'Needs My Decision',
])

<div class="card shadow-sm mb-4 border-danger">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <strong><i class="fa-solid fa-gavel me-1"></i>{{ $title }}</strong>
        <span class="badge text-bg-light">{{ $requests->count() }}</span>
    </div>
    <ul class="list-group list-group-flush">
        @foreach ($requests as $request)
            @php($urgency = $request->decisionUrgency())
            <a href="/tickets/{{ $request->ticket_id }}#note_{{ $request->id }}" class="list-group-item list-group-item-action py-2" data-decision-urgency="{{ $urgency }}">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <span class="text-truncate">
                        <span class="fw-semibold">#{{ $request->ticket_id }} {{ $request->ticket->subject }}</span>
                        <span class="d-block small">{{ \Illuminate\Support\Str::of(strip_tags($request->body))->before("\n")->trim()->limit(140) }}</span>
                        <span class="d-block small text-muted">Asked by {{ $request->user?->name ?? 'Unknown' }}</span>
                    </span>
                    <span class="badge text-bg-{{ $urgency }} flex-shrink-0" title="Waiting since {{ $request->created_at->format('M jS, Y g:ia') }}">{{ $request->created_at->diffForHumans(null, true) }}</span>
                </div>
            </a>
        @endforeach
    </ul>
</div>
