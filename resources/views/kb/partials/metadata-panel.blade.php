<div class="card shadow-sm mb-4">
    <div class="card-header bg-body-secondary">
        <strong>Article Details</strong>
    </div>
    <ul class="list-group list-group-flush">
        <li class="list-group-item">
            <strong>Status:</strong>
            <span class="badge text-bg-{{ $article->status === 'verified' ? 'success' : ($article->status === 'draft' ? 'warning' : 'secondary') }}">
                {{ ucfirst($article->status) }}
            </span>
        </li>
        <li class="list-group-item">
            <strong>Visibility:</strong>
            <span class="badge text-bg-{{ $article->visibility === 'public' ? 'success' : ($article->visibility === 'restricted' ? 'danger' : 'info') }}">
                {{ ucfirst($article->visibility) }}
            </span>
        </li>
        @if($article->category)
            <li class="list-group-item">
                <strong>Category:</strong>
                <a href="{{ route('kb.category', $article->category->slug) }}" class="text-decoration-none">{{ $article->category->name }}</a>
            </li>
        @endif
        @if($article->owner)
            <li class="list-group-item">
                <strong>Owner:</strong> {{ $article->owner->name }}
            </li>
        @endif
        @if($article->tags && $article->tags->count())
            <li class="list-group-item">
                <strong>Tags:</strong>
                <div class="mt-1">
                    @foreach($article->tags as $tag)
                        <a href="{{ route('kb.tag', $tag->slug) }}" class="badge bg-secondary text-decoration-none">{{ $tag->name }}</a>
                    @endforeach
                </div>
            </li>
        @endif
        <li class="list-group-item text-muted small">
            Created {{ $article->created_at->format('M jS, Y g:ia') }}
        </li>
        <li class="list-group-item text-muted small">
            Updated {{ $article->updated_at->format('M jS, Y g:ia') }}
        </li>
        @if($article->reviewed_at)
            <li class="list-group-item text-muted small">
                Reviewed {{ $article->reviewed_at->format('M jS, Y g:ia') }}
            </li>
        @endif
    </ul>
</div>
