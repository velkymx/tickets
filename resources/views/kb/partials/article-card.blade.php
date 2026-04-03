<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="card-title mb-0">
                <a href="{{ route('kb.show', $article->slug) }}" class="text-decoration-none">{{ $article->title }}</a>
            </h5>
            <span class="badge text-bg-{{ $article->status === 'verified' ? 'success' : ($article->status === 'draft' ? 'warning' : 'secondary') }}">
                {{ ucfirst($article->status) }}
            </span>
        </div>

        @if($article->category)
            <span class="badge text-bg-primary me-1">{{ $article->category->name }}</span>
        @endif

        @if($article->visibility !== 'public')
            <span class="badge text-bg-info">{{ ucfirst($article->visibility) }}</span>
        @endif

        @if($article->tags && $article->tags->count())
            <div class="mt-2">
                @foreach($article->tags as $tag)
                    <a href="{{ route('kb.tag', $tag->slug) }}" class="badge bg-secondary text-decoration-none">{{ $tag->name }}</a>
                @endforeach
            </div>
        @endif

        <div class="text-muted small mt-2">
            @if($article->owner)
                By {{ $article->owner->name }}
            @endif
            &middot; Updated {{ $article->updated_at->diffForHumans() }}
        </div>
    </div>
</div>
