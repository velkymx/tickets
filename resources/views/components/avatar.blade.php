@props(['user', 'size' => 36])

<img
    src="{{ $user->avatarUrl($size) }}"
    alt="{{ $user->name }}"
    class="rounded-circle"
    width="{{ $size }}"
    height="{{ $size }}"
    loading="lazy"
>
