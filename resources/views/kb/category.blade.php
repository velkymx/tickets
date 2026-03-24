@extends($layout ?? 'layouts.app')

@section('title', $category->name . ' - Knowledge Base')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('kb.index') }}">Knowledge Base</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
    </ol>
</nav>

<h1 class="mb-2">{{ $category->name }}</h1>
@if($category->description)
    <p class="text-muted mb-4">{{ $category->description }}</p>
@endif

@forelse($articles as $article)
    @include('kb.partials.article-card', ['article' => $article])
@empty
    <div class="alert alert-info">No articles in this category.</div>
@endforelse

@if($articles->hasPages())
    <div class="d-flex justify-content-center">
        {{ $articles->links() }}
    </div>
@endif
@endsection
