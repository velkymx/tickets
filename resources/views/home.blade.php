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
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>T</th>
                    <th>P</th>
                    <th>Status</th>
                    <th>Project</th>
                    <th>Assignee</th>
                    <th>Notes</th>
                    <th>Created</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tickets as $tick)
                <tr>
                    <td><a href="/tickets/{{ $tick->id }}" class="text-decoration-none">#{{ $tick->id }} {{ $tick->subject }}</a></td>
                    <td><span class="badge text-bg-secondary">{{ $tick->type->name }}</span></td>
                    <td><span class="badge text-bg-secondary">{{ $tick->importance->name }}</span></td>
                    <td class="text-center"><span class="badge text-bg-secondary">{{ $tick->status->name }}</span></td>
                    <td>{{ $tick->project->name }}</td>
                    <td>{{ $tick->assignee->name }}</td>
                    <td>
                        @if ($tick->notes()->where('hide', '0')->count() > 0)
                            <span class="badge text-bg-info">{{ $tick->notes()->where('hide', '0')->count() }}</span>
                        @endif
                    </td>
                    <td>{{ $tick->created_at->format('M jS, Y g:ia') }}</td>
                    <td>{{ $tick->updated_at->format('M jS, Y g:ia') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach
@stop
