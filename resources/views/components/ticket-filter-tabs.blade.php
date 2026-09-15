@props(['counts' => ['mine' => 0, 'critical' => 0, 'blocker' => 0]])
@php
    // Each tab is a link that sets query params (composing with sort + pagination).
    // A tab is "active" when the current request already matches its filter.
    $assignee = request('assignee');
    $importance = request('importance_id');

    $isAll = ! $assignee && ! $importance;
    $isMine = $assignee === 'me';
    $isCritical = (string) $importance === '4';
    $isBlocker = (string) $importance === '5';

    $link = fn (array $params) => request()->fullUrlWithQuery(array_merge($params, ['page' => null]));

    // "All" clears the tab-specific params; the others set one and clear the rest.
    $allUrl = $link(['assignee' => null, 'importance_id' => null]);
    $mineUrl = $link(['assignee' => 'me', 'importance_id' => null]);
    $criticalUrl = $link(['importance_id' => 4, 'assignee' => null]);
    $blockerUrl = $link(['importance_id' => 5, 'assignee' => null]);
@endphp

<ul class="nav nav-pills mb-3 gap-1" role="tablist">
    <li class="nav-item">
        <a class="nav-link {{ $isAll ? 'active' : '' }}" href="{{ $allUrl }}">All</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $isMine ? 'active' : '' }}" href="{{ $mineUrl }}">
            Mine <span class="badge text-bg-secondary ms-1">{{ $counts['mine'] }}</span>
        </a>
    </li>
    @if ($counts['critical'] > 0)
        <li class="nav-item">
            <a class="nav-link {{ $isCritical ? 'active' : '' }}" href="{{ $criticalUrl }}">
                Critical <span class="badge text-bg-danger ms-1">{{ $counts['critical'] }}</span>
            </a>
        </li>
    @endif
    @if ($counts['blocker'] > 0)
        <li class="nav-item">
            <a class="nav-link {{ $isBlocker ? 'active' : '' }}" href="{{ $blockerUrl }}">
                Blocker <span class="badge text-bg-dark ms-1">{{ $counts['blocker'] }}</span>
            </a>
        </li>
    @endif
</ul>
