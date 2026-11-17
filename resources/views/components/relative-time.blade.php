@props(['value', 'short' => false])

<time datetime="{{ $value->toIso8601String() }}" title="{{ $value->toDateTimeString() }}">{{ $value->diffForHumans($short ? ['short' => true] : []) }}</time>
