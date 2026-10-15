@extends('layouts.app')

@section('title', 'Milestones List')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Milestones</h1>
        <a href="/milestone/create" class="btn btn-sm btn-primary">Create Milestone</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th class="text-center">Tickets</th>
                    <th>Start</th>
                    <th>Due</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($active as $milestone)
                    <tr>
                        <td>{{ $milestone->name }}</td>
                        <td class="text-center">
                            <span class="badge text-bg-info">{{ $milestone->tickets_open }} / {{ $milestone->tickets_total }}</span>
                        </td>
                        <td>{{ $milestone->start_at ? $milestone->start_at->format('M jS, Y') : '' }}</td>
                        <td>{{ $milestone->due_at ? $milestone->due_at->format('M jS, Y') : '' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="/milestone/show/{{ $milestone->id }}" class="btn btn-sm btn-success">View</a>
                            <a href="/milestone/print/{{ $milestone->id }}" class="btn btn-sm btn-secondary">Print</a>
                            <a href="/milestone/edit/{{ $milestone->id }}" class="btn btn-sm btn-primary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <p class="text-muted mb-0">No active milestones.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $active->links('pagination::bootstrap-5') }}

    @if ($released->isNotEmpty())
        <div class="mt-4 mb-2">
            <strong class="text-success"><i class="bi bi-check-circle-fill me-2"></i> Released Milestones</strong>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="text-center">Tickets</th>
                        <th>Start</th>
                        <th>Due</th>
                        <th>Released</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($released as $milestone)
                        <tr class="text-muted">
                            <td>{{ $milestone->name }}</td>
                            <td class="text-center">
                                <span class="badge text-bg-secondary">{{ $milestone->tickets_open }} / {{ $milestone->tickets_total }}</span>
                            </td>
                            <td>{{ $milestone->start_at ? $milestone->start_at->format('M jS, Y') : '' }}</td>
                            <td>{{ $milestone->due_at ? $milestone->due_at->format('M jS, Y') : '' }}</td>
                            <td>{{ $milestone->end_at ? $milestone->end_at->format('M jS, Y') : '' }}</td>
                            <td class="text-end text-nowrap">
                                <a href="/milestone/show/{{ $milestone->id }}" class="btn btn-sm btn-success">View</a>
                                <a href="/milestone/print/{{ $milestone->id }}" class="btn btn-sm btn-secondary">Print</a>
                                <a href="/milestone/edit/{{ $milestone->id }}" class="btn btn-sm btn-primary">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $released->links('pagination::bootstrap-5') }}
    @endif
@endsection
