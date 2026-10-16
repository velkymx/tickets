@foreach($categories->where('parent_id', $parentId ?? null) as $cat)
    <a href="{{ route('kb.category', $cat->slug) }}"
       class="btn btn-sm {{ (isset($category) && $category->id === $cat->id) || request('category_id') == $cat->id ? 'btn-primary' : 'btn-outline-secondary' }} me-1 mb-1">
        {{ str_repeat('-- ', $depth ?? 0) }}{{ $cat->name }} <span class="badge bg-secondary rounded-pill ms-1">{{ $cat->articles_count }}</span>
    </a>
    @include('kb.partials.category-pills', ['parentId' => $cat->id, 'depth' => ($depth ?? 0) + 1])
@endforeach
