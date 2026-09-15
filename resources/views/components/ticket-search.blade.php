@props([
    'action' => null,
    'query' => '',
    'tokens' => [],
])
@php
    $action = $action ?? url()->current();

    // Presets double as teaching aids: label + the exact query they apply.
    $presets = [
        ['label' => 'All tickets', 'query' => ''],
        ['label' => 'My tickets', 'query' => 'assignee:me'],
        ['label' => 'My open', 'query' => 'assignee:me is:open'],
        ['label' => 'Open', 'query' => 'is:open'],
        ['label' => 'Critical', 'query' => 'importance:critical'],
        ['label' => 'Blockers', 'query' => 'importance:blocker'],
        ['label' => 'Completed', 'query' => 'status:completed'],
    ];

    // Removing a chip strips its raw token from the current query.
    $without = function (string $raw) use ($query) {
        return trim(preg_replace('/\s+/', ' ', str_replace($raw, '', $query)));
    };
@endphp

<form method="GET" action="{{ $action }}" class="mb-2">
    @foreach (['sort' => request('sort'), 'dir' => request('dir')] as $k => $v)
        @if ($v !== null && $v !== '')
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endif
    @endforeach

    <div class="input-group">
        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-sliders me-1"></i> Presets
        </button>
        <ul class="dropdown-menu dropdown-menu-start">
            @foreach ($presets as $preset)
                <li>
                    <a class="dropdown-item d-flex justify-content-between align-items-center gap-3"
                       href="{{ request()->fullUrlWithQuery(['q' => $preset['query'] ?: null, 'page' => null]) }}">
                        <span>{{ $preset['label'] }}</span>
                        <code class="small text-muted">{{ $preset['query'] ?: '—' }}</code>
                    </a>
                </li>
            @endforeach
        </ul>

        <input type="text" name="q" class="form-control" value="{{ $query }}"
               placeholder="Search — e.g. status:active importance:blocker assignee:me login">

        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
    </div>

    <div class="form-text">
        Filter with <code>key:value</code> — status, importance, type, project, milestone, assignee (<code>assignee:me</code>). Plain words search the subject.
    </div>
</form>

@if (! empty($tokens))
    <div class="d-flex flex-wrap gap-1 mb-3">
        @foreach ($tokens as $token)
            <a href="{{ request()->fullUrlWithQuery(['q' => $without($token['raw']) ?: null, 'page' => null]) }}"
               class="badge text-bg-primary text-decoration-none d-inline-flex align-items-center gap-1"
               title="Remove filter">
                {{ $token['raw'] }} <i class="fa-solid fa-xmark"></i>
            </a>
        @endforeach
    </div>
@endif
