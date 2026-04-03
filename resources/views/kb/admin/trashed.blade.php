@extends('layouts.app')

@section('title', 'Trashed Articles')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('kb.index') }}">Knowledge Base</a></li>
        <li class="breadcrumb-item active" aria-current="page">Trashed Articles</li>
    </ol>
</nav>

<h1 class="mb-4">Trashed Articles</h1>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Owner</th>
                    <th>Deleted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                    <tr>
                        <td>{{ $article->title }}</td>
                        <td>
                            @if($article->category)
                                <span class="badge text-bg-primary">{{ $article->category->name }}</span>
                            @else
                                <span class="text-muted">None</span>
                            @endif
                        </td>
                        <td>{{ $article->owner->name ?? 'Unknown' }}</td>
                        <td>{{ $article->deleted_at->format('M jS, Y g:ia') }}</td>
                        <td>
                            <form method="POST" action="{{ route('kb.admin.trashed.restore', $article->id) }}"
                                  onsubmit="return confirm('Restore this article?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fas fa-undo me-1"></i> Restore
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No trashed articles.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($articles->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $articles->links() }}
    </div>
@endif
@endsection
