@extends($layout ?? 'layouts.app')

@section('title', $article->title)

@section('content')
{{-- Breadcrumb --}}
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('kb.index') }}">Knowledge Base</a></li>
        @if($article->category)
            <li class="breadcrumb-item"><a href="{{ route('kb.category', $article->category->slug) }}">{{ $article->category->name }}</a></li>
        @endif
        <li class="breadcrumb-item active" aria-current="page">{{ $article->title }}</li>
    </ol>
</nav>

<div class="row">
    {{-- Main Content --}}
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h1 class="mb-0">{{ $article->title }}</h1>
        </div>

        {{-- Status & Visibility Badges --}}
        <div class="mb-3">
            <span class="badge text-bg-{{ $article->status === 'verified' ? 'success' : ($article->status === 'draft' ? 'warning' : 'secondary') }}">
                {{ ucfirst($article->status) }}
            </span>
            @if($article->visibility !== 'public')
                <span class="badge text-bg-{{ $article->visibility === 'restricted' ? 'danger' : 'info' }}">
                    {{ ucfirst($article->visibility) }}
                </span>
            @endif
        </div>

        {{-- Tags --}}
        @if($article->tags && $article->tags->count())
            <div class="mb-3">
                @foreach($article->tags as $tag)
                    <a href="{{ route('kb.tag', $tag->slug) }}" class="badge bg-secondary text-decoration-none">{{ $tag->name }}</a>
                @endforeach
            </div>
        @endif

        {{-- Article Body --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="article-content">
                    {!! $article->body_html !!}
                </div>
            </div>
        </div>

        {{-- Related Articles --}}
        @if(isset($relatedArticles) && $relatedArticles->count())
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-body-secondary">
                    <strong>Related Articles</strong>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($relatedArticles as $related)
                        <a href="{{ route('kb.show', $related->slug) }}" class="list-group-item list-group-item-action">
                            {{ $related->title }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Metadata Sidebar --}}
    <div class="col-lg-4 mt-4 mt-lg-0">
        @can('update', $article)
            <div class="d-grid gap-2 mb-4">
                <a href="{{ route('kb.edit', $article->slug) }}" class="btn btn-secondary">
                    <i class="fas fa-edit"></i> Edit Article
                </a>
                <a href="{{ route('kb.history', $article->slug) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-history"></i> View History
                </a>
            </div>
        @endcan

        @include('kb.partials.metadata-panel')
    </div>
</div>
@endsection
