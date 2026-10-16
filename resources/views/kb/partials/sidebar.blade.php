<div class="card shadow-sm mb-4">
    <div class="card-header bg-body-secondary">
        <strong>Categories</strong>
    </div>
    <div class="list-group list-group-flush">
        <a href="{{ route('kb.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ !request('category_id') && !request()->routeIs('kb.category') ? 'active' : '' }}">
            All Articles
        </a>
        @include('kb.partials.category-tree', ['parentId' => null, 'depth' => 0])
    </div>
</div>

@auth
    @if(auth()->user()->isKbAdmin())
        <div class="list-group list-group-flush mb-4">
            <a href="{{ route('kb.admin.categories') }}" class="list-group-item list-group-item-action">Manage categories</a>
        </div>
    @endif
@endauth

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
