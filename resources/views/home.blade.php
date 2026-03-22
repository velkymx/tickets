@extends('layouts.app')
@section('title')
User Tickets
@stop
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">My List</h1>
</div>

@if (count($alltickets) == 0)
    <div class="card shadow-sm">
        <div class="card-body text-center p-5">
            <p class="text-muted mb-0">No Tickets Found</p>
        </div>
    </div>
@endif

@foreach ($alltickets as $label => $tickets)
    <h3 class="mb-3">{{ ucwords($label) }}</h3>
    <x-ticket-table :tickets="$tickets" :show-checkbox="false" :show-type="true" :show-estimate="false" :show-created="true" :show-updated="true" />
@endforeach
@stop
