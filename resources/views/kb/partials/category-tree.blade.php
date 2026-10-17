@foreach($categories->where('parent_id', $parentId ?? null) as $cat)
    <a href="{{ route('kb.category', $cat->slug) }}"
       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ (isset($category) && $category->id === $cat->id) || request('category_id') == $cat->id ? 'active' : '' }}">
        <span style="padding-left: {{ ($depth ?? 0) * 14 }}px;">{{ str_repeat('-- ', $depth ?? 0) }}{{ $cat->name }}</span>
        <span class="badge bg-secondary rounded-pill">{{ $cat->articles_count }}</span>
    </a>
    @include('kb.partials.category-tree', ['parentId' => $cat->id, 'depth' => ($depth ?? 0) + 1])
@endforeach
