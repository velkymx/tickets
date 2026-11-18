@props(['title', 'icon', 'count'])

<div class="card shadow-sm mb-4 border-danger">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <strong><i class="fa-solid {{ $icon }} me-1"></i>{{ $title }}</strong>
        <span class="badge text-bg-light">{{ $count }}</span>
    </div>
    <ul class="list-group list-group-flush">{{ $slot }}</ul>
</div>
