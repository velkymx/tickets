@extends($layout ?? 'layouts.app')

@section('title', 'Search: ' . $query)

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('kb.index') }}">Knowledge Base</a></li>
        <li class="breadcrumb-item active" aria-current="page">Search</li>
    </ol>
</nav>

<h1 class="mb-4">Search: {{ $query }}</h1>

{{-- Search Bar --}}
<form action="{{ route('kb.search') }}" method="GET" class="mb-4">
    <div class="input-group">
        <input type="search" name="q" class="form-control" placeholder="Search articles..." value="{{ $query }}">
        <button class="btn btn-outline-secondary" type="submit">
            <i class="fas fa-search"></i> Search
        </button>
    </div>
</form>

@forelse($articles as $article)
    @include('kb.partials.article-card', ['article' => $article])
@empty
    <div class="alert alert-info">No articles found matching "{{ $query }}".</div>
@endforelse

@if($articles->hasPages())
    <div class="d-flex justify-content-center">
        {{ $articles->appends(['q' => $query])->links() }}
    </div>
@endif
@endsection
