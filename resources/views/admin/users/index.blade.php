@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Manage Users</h1>
</div>

<form method="GET" action="{{ route('admin.users.index') }}" class="mb-3">
    <div class="input-group" style="max-width: 420px;">
        <input type="text" name="q" class="form-control" placeholder="Search name or email" value="{{ $q }}">
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th class="text-center">Admin</th>
                <th>KB Role</th>
                <th class="text-center">Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td class="text-center">
                        @if ($user->isAdmin())
                            <span class="badge text-bg-primary">Admin</span>
                        @else
                            <span class="text-muted">&mdash;</span>
                        @endif
                    </td>
                    <td>
                        @if ($user->kb_role)
                            <span class="badge text-bg-secondary">{{ ucfirst($user->kb_role) }}</span>
                        @else
                            <span class="text-muted">&mdash;</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if ($user->active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Edit access</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <p class="text-muted mb-0">No users found.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center my-3">
    {!! $users->links('pagination::bootstrap-5') !!}
</div>
@endsection
