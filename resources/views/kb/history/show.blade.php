@extends('layouts.app')

@section('title', 'Version ' . $versionModel->version_number . ': ' . $versionModel->title)

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('kb.index') }}">Knowledge Base</a></li>
        <li class="breadcrumb-item"><a href="{{ route('kb.show', $article->slug) }}">{{ $article->title }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('kb.history', $article->slug) }}">History</a></li>
        <li class="breadcrumb-item active" aria-current="page">Version {{ $versionModel->version_number }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="mb-1">Version {{ $versionModel->version_number }}: {{ $versionModel->title }}</h1>
        <p class="text-muted mb-0">
            {{ $versionModel->commit_message }}
            &middot; by {{ $versionModel->editor->name ?? 'Unknown' }}
            &middot; {{ $versionModel->created_at->format('M jS, Y g:ia') }}
        </p>
    </div>
    <div class="d-flex gap-2">
        @can('update', $article)
            <form method="POST" action="{{ route('kb.restore', [$article->slug, $versionModel->version_number]) }}"
                  onsubmit="return confirm('Restore this version? This will create a new version from this content.')">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="fas fa-undo me-1"></i> Restore this Version
                </button>
            </form>
        @endcan
        <a href="{{ route('kb.history', $article->slug) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> All Versions
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="article-content">
            {!! $versionModel->body_html !!}
        </div>
    </div>
</div>
@endsection
