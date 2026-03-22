@extends('layouts.app')
@section('title', 'Releases List')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Releases</h1>
    <a href="/releases/create" class="btn btn-sm btn-primary">Create Release</a>
</div>

@if(count($releases) == 0)
    <div class="card shadow-sm">
        <div class="card-body text-center p-5">
            <h2 class="card-title">No Releases Found</h2>
            <p class="card-text text-muted">Release notes are documents, published with the final build, that detail new enhancements and known issues for that version.</p>
            <a href="/releases/create" class="btn btn-primary mt-3">Create A New Release</a>
        </div>
    </div>
@else
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
                @foreach ($releases as $release)
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
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection