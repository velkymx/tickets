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

    @if (trim(strip_tags($user->bio ?? '')) !== '')
    <h3>Bio</h3>
    <div class="mb-4">
        {!! clean($user->bio) !!}
    </div>
    @endif
</div>

<hr class="mb-4" />

{{-- Contribution calendar --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-body-secondary">
        <strong>{{ $contributionTotal }} contributions in the last year</strong>
    </div>
    <div class="card-body">
        @php
            $calEnd = \Carbon\Carbon::today();
            $calStart = $calEnd->copy()->subWeeks(52)->startOfWeek(\Carbon\Carbon::SUNDAY);
            $calWeeks = [];
            $calCursor = $calStart->copy();
            while ($calCursor->lte($calEnd)) {
                $calWeek = [];
                for ($calD = 0; $calD < 7; $calD++) {
                    $calKey = $calCursor->format('Y-m-d');
                    $calWeek[] = [
                        'count' => $calCursor->gt($calEnd) ? null : ($contributions[$calKey] ?? 0),
                        'title' => ($contributions[$calKey] ?? 0).' contributions on '.$calCursor->format('M j, Y'),
                    ];
                    $calCursor->addDay();
                }
                $calWeeks[] = $calWeek;
            }
            $calClass = function ($count) use ($contributionMax) {
                if ($count === 0 || $contributionMax === 0) {
                    return 'bg-body-tertiary';
                }
                if ($count <= $contributionMax * 0.25) {
                    return 'bg-success bg-opacity-25';
                }
                if ($count <= $contributionMax * 0.5) {
                    return 'bg-success bg-opacity-50';
                }
                if ($count <= $contributionMax * 0.75) {
                    return 'bg-success bg-opacity-75';
                }
                return 'bg-success';
            };
        @endphp
        <div class="overflow-x-auto">
            <div class="d-flex gap-1" style="min-width: max-content;">
                @foreach ($calWeeks as $calWeek)
                    <div class="d-flex flex-column gap-1">
                        @foreach ($calWeek as $calDay)
                            @if ($calDay['count'] === null)
                                <div style="width: 12px; height: 12px;"></div>
                            @else
                                <div class="{{ $calClass($calDay['count']) }} rounded-1" style="width: 12px; height: 12px;" title="{{ $calDay['title'] }}"></div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        <div class="d-flex align-items-center gap-1 mt-2 small text-muted">
            <span class="me-1">Less</span>
            <div class="bg-body-tertiary rounded-1" style="width: 12px; height: 12px;"></div>
            <div class="bg-success bg-opacity-25 rounded-1" style="width: 12px; height: 12px;"></div>
            <div class="bg-success bg-opacity-50 rounded-1" style="width: 12px; height: 12px;"></div>
            <div class="bg-success bg-opacity-75 rounded-1" style="width: 12px; height: 12px;"></div>
            <div class="bg-success rounded-1" style="width: 12px; height: 12px;"></div>
            <span class="ms-1">More</span>
        </div>
    </div>
</div>

@if ($isOwnProfile)
@foreach ($alltickets as $label => $tickets)
    @if ($tickets->isNotEmpty())
        <h3 class="mb-3 mt-4">{{ ucwords($label) }}</h3>
        <x-ticket-table :tickets="$tickets" :show-checkbox="false" :show-type="true" :show-estimate="false" :show-updated="true" :small="true" />
    @endif
@endforeach
@endif

@stop
