@props([
    'blockers',
    'title' => 'Blockers',
])

<div class="card shadow-sm mb-4 border-danger">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <strong><i class="fa-solid fa-fire me-1"></i>{{ $title }}</strong>
        <span class="badge text-bg-light">{{ $blockers->count() }}</span>
    </div>
    <ul class="list-group list-group-flush">
        @forelse ($blockers as $ticket)
            <a href="/tickets/{{ $ticket->id }}" class="list-group-item list-group-item-action py-2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <span class="text-truncate">
                        <span class="text-{{ $ticket->importance->class }}" title="{{ $ticket->importance->name }}">
                            <i class="{{ $ticket->importance->icon }}"></i>
                        </span>
                        #{{ $ticket->id }} {{ $ticket->subject }}
                    </span>
                    <span class="badge text-bg-secondary flex-shrink-0">{{ $ticket->status->name }}</span>
                </div>
            </a>
        @empty
            <li class="list-group-item text-muted small text-center py-3">No blockers 🎉</li>
        @endforelse
    </ul>
</div>
