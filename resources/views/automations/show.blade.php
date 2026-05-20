@extends('layouts.app')

@section('title', $automation->name)

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('automations.index') }}">Automations</a></li>
        <li class="breadcrumb-item active">{{ $automation->name }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">{{ $automation->name }}</h1>
        <span class="badge text-bg-secondary me-1">{{ $automation->trigger }}</span>
        @if($automation->enabled)
            <span class="badge text-bg-success">Enabled</span>
        @else
            <span class="badge text-bg-warning">Disabled</span>
        @endif
    </div>
    <a href="{{ route('automations.edit', $automation) }}" class="btn btn-primary">Edit</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<h5 class="mb-3">Execution Runs</h5>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Trigger</th>
                    <th>Started</th>
                    <th>Duration</th>
                    <th>Actions</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($runs as $run)
                    <tr>
                        <td><code>#{{ $run->id }}</code></td>
                        <td>
                            @php
                                $badge = match($run->status) {
                                    'success' => 'text-bg-success',
                                    'failed', 'error' => 'text-bg-danger',
                                    'running' => 'text-bg-primary',
                                    default => 'text-bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $badge }}">{{ $run->status }}</span>
                        </td>
                        <td>{{ $run->trigger_name }}</td>
                        <td>{{ $run->started_at?->diffForHumans() ?? $run->created_at->diffForHumans() }}</td>
                        <td>
                            @if($run->started_at && $run->finished_at)
                                {{ $run->started_at->diffInMilliseconds($run->finished_at) }}ms
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $run->actionLogs->count() }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#run-{{ $run->id }}">
                                Details
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="7" class="p-0">
                            <div class="collapse" id="run-{{ $run->id }}">
                                @foreach($run->actionLogs as $log)
                                    <div class="p-3 border-bottom bg-body-tertiary">
                                        <div class="d-flex gap-3 mb-2 flex-wrap">
                                            <span class="badge {{ $log->status === 'success' ? 'text-bg-success' : 'text-bg-danger' }}">{{ $log->status }}</span>
                                            <code class="small">{{ $log->request_url }}</code>
                                            @if($log->response_code)
                                                <span class="badge text-bg-secondary">HTTP {{ $log->response_code }}</span>
                                            @endif
                                            <span class="text-muted small">{{ $log->attempts }} attempt(s)</span>
                                        </div>
                                        @if($log->request_payload)
                                            <details class="mb-1">
                                                <summary class="small text-muted" style="cursor:pointer">Request Payload</summary>
                                                <pre class="small bg-body p-2 rounded mt-1 overflow-auto">{{ json_encode(json_decode($log->request_payload), JSON_PRETTY_PRINT) }}</pre>
                                            </details>
                                        @endif
                                        @if($log->response_body)
                                            <details>
                                                <summary class="small text-muted" style="cursor:pointer">Response Body</summary>
                                                <pre class="small bg-body p-2 rounded mt-1 overflow-auto">{{ $log->response_body }}</pre>
                                            </details>
                                        @endif
                                        @if($log->error_message)
                                            <div class="alert alert-danger small py-1 mb-0 mt-2">{{ $log->error_message }}</div>
                                        @endif
                                    </div>
                                @endforeach
                                @if($run->actionLogs->isEmpty())
                                    <div class="p-3 text-muted small">No action logs for this run.</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No runs yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $runs->links() }}</div>
@endsection
