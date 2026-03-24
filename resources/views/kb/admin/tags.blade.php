@extends('layouts.app')

@section('title', 'Manage Tags')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('kb.index') }}">Knowledge Base</a></li>
        <li class="breadcrumb-item active" aria-current="page">Manage Tags</li>
    </ol>
</nav>

<h1 class="mb-4">Manage Tags</h1>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Create New Tag --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-body-secondary">
        <strong>Add New Tag</strong>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('kb.admin.tags.store') }}">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label for="tag_name" class="form-label">Name</label>
                    <input type="text" name="name" id="tag_name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success w-100">Add Tag</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Tags Table --}}
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Articles</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tags as $tag)
                    <tr x-data="{ editing: false }">
                        <td>
                            <span x-show="!editing">{{ $tag->name }}</span>
                        </td>
                        <td>
                            <span x-show="!editing" class="text-muted">{{ $tag->slug }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $tag->articles_count }}</span>
                        </td>
                        <td>
                            <div x-show="!editing" class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary" @click="editing = true">Edit</button>
                                <form method="POST" action="{{ route('kb.admin.tags.destroy', $tag->id) }}"
                                      onsubmit="return confirm('Delete this tag?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>

                            <form x-show="editing" x-cloak method="POST" action="{{ route('kb.admin.tags.update', $tag->id) }}" class="d-flex gap-1">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $tag->name }}" class="form-control form-control-sm" style="width: 150px;">
                                <button type="submit" class="btn btn-sm btn-success">Save</button>
                                <button type="button" class="btn btn-sm btn-secondary" @click="editing = false">Cancel</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
