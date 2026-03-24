@extends($layout ?? 'layouts.app')

@section('title', 'Knowledge Base')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Knowledge Base</h1>
    @can('create', \App\Models\KbArticle::class)
        <a href="{{ route('kb.create') }}" class="btn btn-sm btn-primary">Create Article</a>
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

{{-- Category Filter Pills --}}
<div class="mb-4">
    <a href="{{ route('kb.index') }}" class="btn btn-sm {{ !request('category_id') && !request()->routeIs('kb.category') ? 'btn-primary' : 'btn-outline-secondary' }} me-1 mb-1">
        All Articles
    </a>
    @foreach($categories as $cat)
        <a href="{{ route('kb.category', $cat->slug) }}"
           class="btn btn-sm {{ (isset($category) && $category->id === $cat->id) || request('category_id') == $cat->id ? 'btn-primary' : 'btn-outline-secondary' }} me-1 mb-1">
            {{ $cat->name }} <span class="badge bg-secondary rounded-pill ms-1">{{ $cat->articles_count }}</span>
        </a>
    @endforeach
    @auth
        <span class="mx-1 text-muted">|</span>
        <a href="{{ route('kb.index', ['status' => 'draft']) }}" class="btn btn-sm {{ request('status') === 'draft' ? 'btn-warning' : 'btn-outline-secondary' }} me-1 mb-1">
            Drafts
        </a>
        <a href="{{ route('kb.index', ['status' => 'verified']) }}" class="btn btn-sm {{ request('status') === 'verified' ? 'btn-success' : 'btn-outline-secondary' }} me-1 mb-1">
            Verified
        </a>
    @endauth
</div>

{{-- Article List --}}
@forelse($articles as $article)
    @include('kb.partials.article-card', ['article' => $article])
@empty
    <table class="table table-striped">
        <tbody>
            <tr>
                <td class="text-center py-5">
                    <p class="text-muted mb-0">No articles found.</p>
                </td>
            </tr>
        </tbody>
    </table>
@endforelse

@if($articles->hasPages())
    <div class="d-flex justify-content-center">
        {{ $articles->links() }}
    </div>
@endif
@endsection
