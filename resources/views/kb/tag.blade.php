@extends($layout ?? 'layouts.app')

@section('title', $tag->name . ' - Knowledge Base')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('kb.index') }}">Knowledge Base</a></li>
        <li class="breadcrumb-item active" aria-current="page">Tag: {{ $tag->name }}</li>
    </ol>
</nav>

<h1 class="mb-4">{{ $tag->name }}</h1>

@forelse($articles as $article)
    @include('kb.partials.article-card', ['article' => $article])
@empty
    <div class="alert alert-info">No articles with this tag.</div>
@endforelse

@if($articles->hasPages())
    <div class="d-flex justify-content-center">
        {{ $articles->links() }}
    </div>
@endif
@endsection
