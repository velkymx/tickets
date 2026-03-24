<div class="card shadow-sm mb-4">
    <div class="card-header bg-body-secondary">
        <strong>Categories</strong>
    </div>
    <div class="list-group list-group-flush">
        <a href="{{ route('kb.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ !request('category_id') && !request()->routeIs('kb.category') ? 'active' : '' }}">
            All Articles
        </a>
        @foreach($categories as $cat)
            <a href="{{ route('kb.category', $cat->slug) }}"
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ (isset($category) && $category->id === $cat->id) || request('category_id') == $cat->id ? 'active' : '' }}">
                {{ $cat->name }}
                <span class="badge bg-secondary rounded-pill">{{ $cat->articles_count }}</span>
            </a>
        @endforeach
    </div>
</div>

@auth
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-body-secondary">
            <strong>Quick Filters</strong>
        </div>
        <div class="list-group list-group-flush">
            <a href="{{ route('kb.index', ['status' => 'draft']) }}" class="list-group-item list-group-item-action">
                Drafts
            </a>
            <a href="{{ route('kb.index', ['status' => 'verified']) }}" class="list-group-item list-group-item-action">
                Verified
            </a>
        </div>
    </div>
@endauth
