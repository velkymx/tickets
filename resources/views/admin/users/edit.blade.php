@extends('layouts.app')

@section('title', 'Edit User Access')

@section('content')
@php $isSelf = $user->id === auth()->id(); @endphp

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Manage Users</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-body-secondary">
                <strong>Access for {{ $user->name }}</strong>
                <div class="small text-muted">{{ $user->email }}</div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="admin" value="0">
                        <input type="checkbox" class="form-check-input" role="switch" id="admin"
                               name="admin" value="1" @checked($user->isAdmin()) @disabled($isSelf)>
                        <label class="form-check-label" for="admin">Administrator</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="active" value="0">
                        <input type="checkbox" class="form-check-input" role="switch" id="active"
                               name="active" value="1" @checked($user->active) @disabled($isSelf)>
                        <label class="form-check-label" for="active">Active (can log in)</label>
                    </div>

                    <div class="mb-3">
                        <label for="kb_role" class="form-label">Knowledge Base role</label>
                        <select name="kb_role" id="kb_role" class="form-select">
                            <option value="" @selected(! $user->kb_role)>None</option>
                            <option value="author" @selected($user->kb_role === 'author')>Author</option>
                            <option value="admin" @selected($user->kb_role === 'admin')>Admin</option>
                        </select>
                    </div>

                    @if ($isSelf)
                        <p class="small text-muted">You cannot change your own administrator or active status.</p>
                    @endif

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
