@extends('layouts.app')

@section('title', 'Activity Center')

@section('content')
    @php
        $filters = [
            'all' => 'All',
            'mentions' => 'Mentions',
            'watching' => 'Watching',
            'assigned' => 'Assigned',
            'replies' => 'Replies',
        ];
    @endphp

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <p class="text-uppercase small text-muted fw-semibold mb-2">Inbox</p>
            <h1 class="h2 mb-1">Activity Center</h1>
            <p class="text-muted mb-0">Recent mentions, watcher updates, replies, and assignment changes.</p>
        </div>

        <form method="POST" action="/activity/read-all">
            @csrf
            <button type="submit" class="btn btn-outline-primary">Mark all</button>
        </form>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        @foreach ($filters as $key => $label)
            <a
                href="{{ $key === 'all' ? '/activity' : '/activity?filter='.$key }}"
                class="btn {{ $filter === $key ? 'btn-primary' : 'btn-outline-secondary' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="d-flex justify-content-between align-items-center border-top border-bottom py-3 mb-4 text-muted small text-uppercase fw-semibold">
        <span>{{ $notifications->count() }} items</span>
        <span>Unread</span>
    </div>

    <div class="vstack gap-3">
        @forelse ($notifications as $notification)
            @php
                $type = $notification->data['type'] ?? 'activity';
                $excerpt = $notification->data['excerpt'] ?? $notification->data['message'] ?? '';
                $url = $notification->data['url'] ?? '/activity';
                $dotClass = $notification->read_at ? 'bg-secondary-subtle text-secondary' : 'bg-danger-subtle text-danger';
                $title = match ($type) {
                    'mention' => '@'.$notification->data['actor_name'].' mentioned you',
                    'reply' => $notification->data['actor_name'].' replied to your comment',
                    'watching' => 'Ticket update',
                    'assigned' => 'Assignment update',
                    default => 'Activity update',
                };
            @endphp

            <article class="border rounded-4 p-3 p-lg-4 {{ $notification->read_at ? 'bg-body-tertiary' : 'bg-body' }}">
                <div class="d-flex gap-3 align-items-start">
                    <div class="rounded-circle {{ $dotClass }} d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 2.5rem; height: 2.5rem;">
                        {{ $notification->read_at ? '•' : '●' }}
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-2">
                            <div>
                                <h2 class="h6 mb-1">{{ $title }}</h2>
                                <p class="mb-0 text-muted">{{ $excerpt }}</p>
                            </div>
                            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ $url }}" class="btn btn-sm btn-outline-primary">Open</a>

                            @if (! $notification->read_at)
                                <form method="POST" action="/activity/read/{{ $notification->id }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Mark read</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="border rounded-4 p-5 text-center text-muted bg-body-tertiary">
                No activity yet.
            </div>
        @endforelse
    </div>
@endsection
