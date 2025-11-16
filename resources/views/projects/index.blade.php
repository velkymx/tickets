@extends('layouts.app')
@section('title', 'Ticket List')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Projects</h1>
    {{-- Replaced pull-right with d-flex utilities --}}
    <div>
        <a href="/projects/create" class="btn btn-sm btn-primary">Create Project</a>
    </div>
</div>
<hr class="mb-4">

{{-- Replaced table-striped with B5 table classes and table-responsive for mobile view --}}
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Name</th>
                <th style="width: 150px;">Tickets</th>
                <th style="width: 150px;"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($projects as $project)
            <tr>
                <td>{{ $project->name }}</td>
                <td>
                    {{-- Status counts shown using B5 badge classes --}}
                    <span class="badge text-bg-secondary me-2">
                        {{ $project->tickets()->whereIn('status_id',['1','2','3','6'])->count() }}
                    </span>
                    /
                    <span class="badge text-bg-light border ms-2 text-dark">
                        {{ $project->tickets()->count() }}
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
        </tbody>
    </table>
</div>
@endsection