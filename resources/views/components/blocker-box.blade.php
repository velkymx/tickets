@props([
    'blockers',
    'title' => 'Blockers',
])

<x-card-list :title="$title" icon="fa-fire" :count="$blockers->count()">
    @forelse ($blockers as $ticket)
        <a href="/tickets/{{ $ticket->id }}" class="list-group-item list-group-item-action py-2">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <span class="text-truncate">
                    <x-importance-badge :importance="$ticket->importance" show-label />
                    #{{ $ticket->id }} {{ $ticket->subject }}
                </span>
                <span class="badge text-bg-secondary flex-shrink-0">{{ $ticket->status->name }}</span>
            </div>
        </a>
    @empty
        <li class="list-group-item text-muted small text-center py-3">No blockers 🎉</li>
    @endforelse
</x-card-list>
