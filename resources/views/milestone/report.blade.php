@extends('layouts.app')
@section('title', $milestone->name . ' - Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>{{ $milestone->name }} - Sprint Report</h1>
    <div class="btn-group" role="group">
        <a href="/milestone/show/{{ $milestone->id }}" class="btn btn-sm btn-outline-secondary">Back to Milestone</a>
        <a href="/milestone/print/{{ $milestone->id }}" class="btn btn-sm btn-secondary">Print</a>
    </div>
</div>

{{-- Milestone Info Header --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h4>{{ $milestone->name }}</h4>
                @if($milestone->description)
                    <p class="text-muted">{{ $milestone->description }}</p>
                @endif
                <p>
                    <strong>Owner:</strong> {{ $milestone->owner->name ?? 'Unassigned' }}<br>
                    <strong>Scrummaster:</strong> {{ $milestone->scrummaster->name ?? 'Unassigned' }}
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <p>
                    <strong>Start:</strong> {{ $milestone->start_at ? \Carbon\Carbon::parse($milestone->start_at)->format('M j, Y') : 'N/A' }}<br>
                    <strong>Due:</strong> {{ $milestone->due_at ? \Carbon\Carbon::parse($milestone->due_at)->format('M j, Y') : 'N/A' }}<br>
                    @if($milestone->end_at)
                        <strong>Released:</strong> {{ \Carbon\Carbon::parse($milestone->end_at)->format('M j, Y') }}<br>
                    @endif
                    <strong>Duration:</strong> {{ $duration > 0 ? $duration . ' days' : 'N/A' }}
                </p>
                @if($milestone->end_at)
                    <span class="badge text-bg-success">Released</span>
                @else
                    <span class="badge text-bg-warning">In Progress</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Summary Stats --}}
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3>{{ $totalTickets }}</h3>
                <p class="mb-0">Total Tickets</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3>{{ $completedTickets }}</h3>
                <p class="mb-0">Completed</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3>{{ $openTickets }}</h3>
                <p class="mb-0">Open</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3>{{ $completionPercentage }}%</h3>
                <p class="mb-0">Complete</p>
            </div>
        </div>
    </div>
</div>

{{-- Velocity Metrics --}}
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3>{{ $totalStoryPoints ?? 0 }}</h3>
                <p class="mb-0">Total Story Points</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3>{{ $completedStoryPoints ?? 0 }}</h3>
                <p class="mb-0">Completed Points</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3>{{ $remainingStoryPoints ?? 0 }}</h3>
                <p class="mb-0">Remaining Points</p>
            </div>
        </div>
    </div>
</div>

{{-- Breakdown Tables --}}
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Status Breakdown</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th class="text-end">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statusBreakdown as $status)
                            <tr>
                                <td>{{ $status['name'] }}</td>
                                <td class="text-end">{{ $status['count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Type Breakdown</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th class="text-end">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($typeBreakdown as $type)
                            <tr>
                                <td>{{ $type['name'] }}</td>
                                <td class="text-end">{{ $type['count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Burndown Chart --}}
@if(!empty($burndownData) && count($burndownData['labels']) > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Burndown Chart</h5>
            </div>
            <div class="card-body">
                <canvas id="burndownChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Team Members --}}
@if($teamHours->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Team Contributions (Hours Logged)</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Team Member</th>
                            <th class="text-end">Tickets Worked</th>
                            <th class="text-end">Hours Logged</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($teamHours as $member)
                            <tr>
                                <td>{{ $member['user_name'] }}</td>
                                <td class="text-end">{{ $member['ticket_count'] }}</td>
                                <td class="text-end">{{ number_format($member['total_hours'], 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Ticket List --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Tickets</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Assignee</th>
                            <th class="text-end">Points</th>
                            <th class="text-end">Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ticketDetails as $ticket)
                            <tr>
                                <td>#{{ $ticket['id'] }}</td>
                                <td>{{ $ticket['subject'] }}</td>
                                <td>{{ $ticket['status'] }}</td>
                                <td>{{ $ticket['type'] }}</td>
                                <td>{{ $ticket['assignee'] }}</td>
                                <td class="text-end">{{ $ticket['storypoints'] }}</td>
                                <td class="text-end">{{ number_format($ticket['logged_hours'], 1) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center">No tickets in this milestone</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
@if(!empty($burndownData) && count($burndownData['labels']) > 0)
<script>
    window.burndownChartData = {
        labels: {!! json_encode($burndownData['labels']) !!},
        ideal: {!! json_encode($burndownData['ideal']) !!},
        actual: {!! json_encode($burndownData['actual']) !!}
    };
</script>
@endif
@endpush
