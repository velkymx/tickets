@props(['column', 'label', 'class' => ''])
@php
    $current = request('sort');
    $currentDir = strtolower((string) request('dir')) === 'desc' ? 'desc' : 'asc';
    $active = $current === $column;
    $nextDir = $active && $currentDir === 'asc' ? 'desc' : 'asc';
    // Preserve existing filters/perpage; reset page so a new sort starts on page 1.
    $url = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $nextDir, 'page' => null]);
    $icon = $active ? ($currentDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort text-muted';
@endphp
<th {{ $attributes->merge(['class' => $class]) }}>
    <a href="{{ $url }}" class="text-decoration-none text-reset">
        {{ $label }} <i class="fa-solid {{ $icon }} small"></i>
    </a>
</th>
