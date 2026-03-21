@extends('layouts.app')
@section('title')
Releases List
@endsection
@section('content')
<h1>{{ $release->title }}</h1>
<hr>
<div class="row-fluid">
    Release Date: {{ $release->completed_at ? date_format($release->completed_at, 'm/d/Y') : 'Not set' }}
</div>
<hr>
<div class="row-fluid">
    {!! clean($release->body ?? '') !!}
</div>
<hr>
@if($release->tickets->count() == 0)
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> Add tickets to your release from the All Tickets tab using the bulk update feature.</div>   
@else
    @foreach($projects as $project)
        <h3><i class='fas fa-folder'></i> {{ $project['project'] }}</h3><hr>

        @foreach($project['tickets'] as $type => $tickets)    
            @if(count($tickets) > 0) 
                <div class="row">
                    <div class="col-md-3"><h3><i class="{{ $types[$type] }}" title="{{ $type }}"></i> {{ $type }}s</h3></div>
                    <div class="col-md-9">
                        <ul class="list-group">
                            @foreach($tickets as $ticket)
                                <li class="list-group-item">{{ $ticket->subject }} (<a href="/tickets/{{ $ticket->id }}">#{{ $ticket->id }}</a>)</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        @endforeach
        <br><br>
    @endforeach
@endif
@endsection
