@extends($layout ?? 'layouts.app')

@section('title', 'Knowledge Base')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Knowledge Base</h1>
    @can('create', \App\Models\KbArticle::class)
        <a href="{{ route('kb.create') }}" class="btn btn-success">
            <i class="fas fa-plus me-1"></i> New Article
        </a>
    @endcan
</div>

{{-- Search Bar --}}
<form action="{{ route('kb.search') }}" method="GET" class="mb-4">
    <div class="input-group">
        <input type="search" name="q" class="form-control" placeholder="Search articles..." value="{{ request('q') }}">
        <button class="btn btn-outline-secondary" type="submit">
            <i class="fas fa-search"></i> Search
        </button>
    </div>
</form>

<div class="row">
    {{-- Sidebar --}}
    <div class="col-lg-3">
        @include('kb.partials.sidebar')
    </div>

    {{-- Main Content --}}
    <div class="col-lg-9">
        @forelse($articles as $article)
            @include('kb.partials.article-card', ['article' => $article])
        @empty
            <div class="alert alert-info">No articles found.</div>
        @endforelse

        @if($articles->hasPages())
            <div class="d-flex justify-content-center">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
