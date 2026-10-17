@extends('layouts.app')

@section('title', 'System Health')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">System Health</h1>
</div>

@if ($summary['problems'] > 0)
    <div class="alert alert-danger" role="alert">
        {{ $summary['problems'] }} {{ Str::plural('problem', $summary['problems']) }} need attention.
    </div>
@elseif ($summary['warnings'] > 0)
    <div class="alert alert-warning" role="alert">
        {{ $summary['warnings'] }} {{ Str::plural('warning', $summary['warnings']) }} to review.
    </div>
@else
    <div class="alert alert-success" role="alert">All checks passed.</div>
@endif

@foreach ($report as $section => $results)
    <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold">{{ $section }}</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <tbody>
                    @foreach ($results as $result)
                        <tr>
                            <td class="text-nowrap" style="width: 220px;">{{ $result->label }}</td>
                            <td style="width: 120px;">
                                <span class="badge {{ match ($result->status->value) {
                                    'OK', 'Enabled' => 'bg-success',
                                    'Warning', 'Disabled' => 'bg-warning text-dark',
                                    default => 'bg-danger',
                                } }}">{{ $result->status->value }}</span>
                            </td>
                            <td>
                                {{ $result->value }}
                                @if ($result->fix)
                                    <div class="small text-body-secondary">Fix: {{ $result->fix }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($section === 'Queue')
            <div class="card-body border-top" data-health-ping
                data-ping-url="{{ route('admin.health.ping') }}"
                data-status-url="{{ route('admin.health.ping.status', ['token' => 'TOKEN']) }}">
                @if (! $sharedCache)
                    <p class="text-body-secondary mb-0">Can&rsquo;t verify: the cache is not shared with the worker.</p>
                @elseif ($queueConnection === 'sync')
                    <p class="text-body-secondary mb-0">The sync driver runs jobs inside the request, so there is no worker to ping.</p>
                @else
                    <button type="button" class="btn btn-sm btn-outline-primary" data-ping-start>Ping worker</button>
                    <span class="ms-2 small" data-ping-result role="status"></span>
                @endif
            </div>
        @endif
    </div>
@endforeach
@endsection
