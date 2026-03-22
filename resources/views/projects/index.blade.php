@extends('layouts.app')
@section('title', 'Ticket List')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Projects</h1>
    <a href="/projects/create" class="btn btn-sm btn-primary">Create Project</a>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Name</th>
                <th class="col-2">Tickets</th>
                <th class="col-2"></th>
            </tr>
        </thead>
        <tbody>
            @if($projects->isEmpty())
                <tr>
                    <td colspan="3" class="text-center p-4">
                        <p class="text-muted mb-2">No projects found.</p>
                        <a href="/projects/create" class="btn btn-sm btn-primary">Create Project</a>
                    </td>
                </tr>
            @else
            @foreach ($projects as $project)
            <tr>
                <td>{{ $project->name }}</td>
                <td>
                    {{-- Status counts shown using B5 badge classes --}}
                    <span class="badge text-bg-secondary me-2">
                        {{ $project->active_tickets_count }}
                    </span>
                    /
                    <span class="badge text-bg-secondary ms-2">
                        {{ $project->total_tickets_count }}
                    </span>
                </td>
                {{-- Button Group for actions --}}
                <td class="text-end">
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="/projects/show/{{ $project->id }}" class="btn btn-success">View</a> 
                        <a href="/projects/edit/{{ $project->id }}" class="btn btn-primary">Edit</a>
                    </div>
                </td>
            </tr>
            @endforeach
            @endif
        </tbody>
    </table>
</div>
@endsection