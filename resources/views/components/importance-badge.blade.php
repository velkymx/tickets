@props(['importance', 'showLabel' => false, 'pill' => false])

@if($importance)
    @if($pill)
        <span class="badge text-bg-{{ $importance->class }}" title="Priority: {{ $importance->name }}">@if(! empty($importance->icon))<i class="{{ $importance->icon }}"></i> @endif{{ $importance->name }}</span>
    @else
        <span class="text-{{ $importance->class }}" title="Priority: {{ $importance->name }}"><i class="{{ $importance->icon }}" aria-hidden="true"></i>@if($showLabel) {{ $importance->name }}@endif</span>
    @endif
@else
    @if($pill)
        <span class="badge text-bg-secondary" title="Priority: none">None</span>
    @else
        <span class="text-muted" title="Priority: none">@if($showLabel) None @else - @endif</span>
    @endif
@endif
