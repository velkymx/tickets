@extends('layouts.app')
@section('title', 'User Tickets')
@section('content')
<div class="mb-4">
    <h1>{{ $user->name }} @if($user->admin == 1) <span class="badge text-bg-secondary">Admin</span>@endif</h1>
    @if ($user->title)
    <p class="lead text-muted">{{ $user->title }}</p>
    @endif
    
    <div class="row g-2 mb-3 small">
        @if ($user->email)
        <div class="col-md-auto">
            <strong>Email:</strong> <a href="mailto:{{ $user->email }}" class="text-decoration-none">{{ $user->email }}</a>
        </div>
        @endif
        @if ($user->phone)
        <div class="col-md-auto">
            <strong>Phone Number:</strong> <a href="tel:{{ $user->phone }}" class="text-decoration-none">{{ $user->phone }}</a>
        </div>
        @endif
        @if ($currenttime)
        <div class="col-md-auto">
            <strong>Local time:</strong> {{ $currenttime }}
        </div>
        @endif
    </div>

    @if ($user->bio)
    <h3>Bio</h3>
    <div class="mb-4">
        {!! clean($user->bio) !!}
    </div>
    @endif
</div>

<hr class="mb-4" />

@foreach ($alltickets as $label => $tickets)
    @if ($tickets->isNotEmpty())
        <h3 class="mb-3 mt-4">{{ ucwords($label) }}</h3>
        <x-ticket-table :tickets="$tickets" :show-checkbox="false" :show-type="true" :show-estimate="false" :show-created="true" :show-updated="true" :small="true" />
    @endif
@endforeach

@stop
