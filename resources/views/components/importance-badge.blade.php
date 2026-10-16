@props(['importance', 'showLabel' => false])

@if($importance)
    <span class="text-{{ $importance->class }}" title="Priority: {{ $importance->name }}">
        <i class="{{ $importance->icon }}" aria-hidden="true"></i>@if($showLabel) {{ $importance->name }}@endif
    </span>
@else
    <span class="text-muted" title="Priority: none">@if($showLabel) None @else — @endif</span>
@endif
