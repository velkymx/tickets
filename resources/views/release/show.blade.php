@extends('layouts.app')
@section('title')
Release: {{ $release->title }}
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">{{ $release->title }}</h1>
    <a href="/release/edit/{{ $release->id }}" class="btn btn-sm btn-primary">Edit Release</a>
</div>

@if($release->completed_at)
    <p class="text-muted mb-4">Released: {{ $release->completed_at->format('m/d/Y') }}</p>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-body">
        {!! clean($release->body ?? '<p class="text-muted">No release notes.</p>') !!}
    </div>
</div>

@if($release->tickets->count() == 0)
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> Add tickets to your release from the All Tickets tab using the bulk update feature.</div>   
@else
    @foreach($projects as $project)
        <h3 class="mb-3"><i class='fas fa-folder me-2' aria-hidden="true"></i>{{ $project['project'] }}</h3>

        @foreach($project['tickets'] as $type => $tickets)    
            @if(count($tickets) > 0) 
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="mb-3"><i class="{{ $types[$type] }}" title="{{ $type }}" aria-hidden="true"></i> {{ $type }}s</h4>
                        <ul class="list-group">
                            @foreach($tickets as $ticket)
                                <li class="list-group-item">
                                    {{ $ticket->subject }} (<a href="/tickets/{{ $ticket->id }}">#{{ $ticket->id }}</a>)
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        @endforeach
    @endforeach
@endif
@endsection
