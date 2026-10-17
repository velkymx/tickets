@foreach($categories->where('parent_id', $parentId ?? null) as $category)
    @if(($excludeId ?? null) != $category->id)
        <option value="{{ $category->id }}" @selected(($selectedId ?? null) == $category->id)>{{ str_repeat('-- ', $depth ?? 0) }}{{ $category->name }}</option>
    @endif
    @include('kb.partials.category-options', ['parentId' => $category->id, 'depth' => ($depth ?? 0) + 1, 'selectedId' => $selectedId ?? null, 'excludeId' => $excludeId ?? null])
@endforeach
