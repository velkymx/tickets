@extends('layouts.app')
@section('title', 'Release: ' . $release->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">{{ $release->title }}</h1>
    <a href="/release/edit/{{ $release->id }}" class="btn btn-sm btn-primary">Edit Release</a>
</div>

<div class="row">
    {{-- Main Content --}}
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                {!! clean($release->body ?? '<p class="text-muted">No release notes.</p>') !!}
            </div>
        </div>

        @if($release->tickets->count() == 0)
            <div class="text-center py-5">
                <p class="text-muted mb-0">No tickets in this release. Add tickets from the All Tickets tab using the bulk update feature.</p>
            </div>
        @else
            @foreach($projects as $project)
                <h3 class="mb-3"><i class='fas fa-folder me-2' aria-hidden="true"></i>{{ $project['project'] }}</h3>

                @foreach($project['tickets'] as $type => $tickets)
                    @if(count($tickets) > 0)
                        <div class="mb-4">
                            <h4 class="mb-3"><i class="{{ $types[$type] }}" title="{{ $type }}" aria-hidden="true"></i> {{ $type }}s</h4>
                            <ul class="list-group">
                                @foreach($tickets as $ticket)
                                    <li class="list-group-item">
                                        {{ $ticket->subject }} (<a href="/tickets/{{ $ticket->id }}">#{{ $ticket->id }}</a>)
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            @endforeach
        @endif
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4 mt-4 mt-lg-0">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-body-secondary">
                <strong>Release Details</strong>
            </div>
            <ul class="list-group list-group-flush">
                @if($release->completed_at)
                    <li class="list-group-item">
                        <strong>Released:</strong> {{ $release->completed_at->format('M jS, Y') }}
                    </li>
                @endif
                @if($release->started_at)
                    <li class="list-group-item">
                        <strong>Started:</strong> {{ $release->started_at->format('M jS, Y') }}
                    </li>
                @endif
                <li class="list-group-item">
                    <strong>Tickets:</strong> <span class="badge text-bg-secondary">{{ $release->tickets->count() }}</span>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
