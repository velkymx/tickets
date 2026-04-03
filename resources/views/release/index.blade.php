@extends('layouts.app')
@section('title', 'Releases List')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Releases</h1>
    <a href="/releases/create" class="btn btn-sm btn-primary">Create Release</a>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Name</th>
                <th class="col-2">Tickets</th>
                <th class="col-2">Date</th>
                <th class="col-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($releases as $release)
            <tr>
                <td>{{ $release->title }}</td>
                <td><span class="badge text-bg-secondary">{{ $release->tickets()->count() }}</span></td>
                <td>{{ date_format(date_create($release->completed_at),'Y-m-d') }}</td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="/release/{{ $release->id }}" class="btn btn-success">View</a>
                        <a href="/release/edit/{{ $release->id }}" class="btn btn-primary">Edit</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center py-5">
                    <p class="text-muted mb-0">No releases found.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection