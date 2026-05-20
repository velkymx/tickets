@extends('layouts.app')

@section('title', 'Automations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Automations</h1>
    <a href="{{ route('automations.create') }}" class="btn btn-success">New Automation</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Trigger</th>
                    <th>Status</th>
                    <th>Runs</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($automations as $automation)
                    <tr>
                        <td>
                            <a href="{{ route('automations.show', $automation) }}">{{ $automation->name }}</a>
                            @if($automation->description)
                                <div class="text-muted small">{{ Str::limit($automation->description, 60) }}</div>
                            @endif
                        </td>
                        <td><span class="badge text-bg-secondary">{{ $automation->trigger }}</span></td>
                        <td>
                            @if($automation->enabled)
                                <span class="badge text-bg-success">Enabled</span>
                            @else
                                <span class="badge text-bg-warning">Disabled</span>
                            @endif
                        </td>
                        <td>{{ $automation->runs_count }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('automations.edit', $automation) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('automations.destroy', $automation) }}" onsubmit="return confirm('Delete this automation?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No automations yet. <a href="{{ route('automations.create') }}">Create one.</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $automations->links() }}</div>
@endsection
