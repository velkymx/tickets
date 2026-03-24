@extends('layouts.app')

@section('title', 'Manage Categories')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('kb.index') }}">Knowledge Base</a></li>
        <li class="breadcrumb-item active" aria-current="page">Manage Categories</li>
    </ol>
</nav>

<h1 class="mb-4">Manage Categories</h1>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Create New Category --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-body-secondary">
        <strong>Add New Category</strong>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('kb.admin.categories.store') }}">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label for="description" class="form-label">Description</label>
                    <input type="text" name="description" id="description" class="form-control">
                </div>
                <div class="col-md-2">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" class="form-control" value="0">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Categories Table --}}
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Sort Order</th>
                    <th>Articles</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr x-data="{ editing: false }">
                        <td>
                            <span x-show="!editing">{{ $category->name }}</span>
                        </td>
                        <td>
                            <span x-show="!editing">{{ $category->description }}</span>
                        </td>
                        <td>
                            <span x-show="!editing">{{ $category->sort_order }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $category->articles_count }}</span>
                        </td>
                        <td>
                            <div x-show="!editing" class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary" @click="editing = true">Edit</button>
                                @if($category->articles_count === 0)
                                    <form method="POST" action="{{ route('kb.admin.categories.destroy', $category->id) }}"
                                          onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </div>

                            <form x-show="editing" x-cloak method="POST" action="{{ route('kb.admin.categories.update', $category->id) }}" class="d-flex gap-1">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $category->name }}" class="form-control form-control-sm" style="width: 120px;">
                                <input type="text" name="description" value="{{ $category->description }}" class="form-control form-control-sm" style="width: 150px;">
                                <input type="number" name="sort_order" value="{{ $category->sort_order }}" class="form-control form-control-sm" style="width: 70px;">
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
