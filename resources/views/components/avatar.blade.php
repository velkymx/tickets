@props(['user', 'size' => 36])

@php
    $gravatarUrl = "https://www.gravatar.com/avatar/{$user->gravatar_hash}?s={$size}&d=mp";
@endphp

<img
    src="{{ $gravatarUrl }}"
    alt="{{ $user->name }}"
    class="rounded-circle"
    width="{{ $size }}"
    height="{{ $size }}"
    loading="lazy"
>
