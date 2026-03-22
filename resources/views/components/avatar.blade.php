@props(['user', 'size' => 36])

@php
    $hash = md5(strtolower(trim($user->email ?? '')));
    $gravatarUrl = "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
@endphp

<img
    src="{{ $gravatarUrl }}"
    alt="{{ $user->name }}"
    class="rounded-circle"
    width="{{ $size }}"
    height="{{ $size }}"
    loading="lazy"
>
