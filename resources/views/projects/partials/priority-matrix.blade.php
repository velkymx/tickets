@php
    use App\Services\PriorityMatrixService;

    $sections = [
        PriorityMatrixService::QUICK_WINS     => ['label' => 'Quick Wins',     'icon' => 'fa-star',            'hint' => 'High impact, low effort', 'class' => 'success'],
        PriorityMatrixService::MAJOR_PROJECTS => ['label' => 'Major Projects', 'icon' => 'fa-mountain',        'hint' => 'High impact, high effort', 'class' => 'primary'],
        PriorityMatrixService::FILL_INS       => ['label' => 'Fill-Ins',       'icon' => 'fa-puzzle-piece',    'hint' => 'Low impact, low effort', 'class' => 'secondary'],
        PriorityMatrixService::THANKLESS      => ['label' => 'Thankless Tasks','icon' => 'fa-hourglass-half',  'hint' => 'Low impact, high effort', 'class' => 'warning'],
        PriorityMatrixService::UNESTIMATED    => ['label' => 'Unestimated',    'icon' => 'fa-circle-question', 'hint' => 'Add an estimate to place these', 'class' => 'dark'],
    ];
@endphp

<div class="card shadow-sm mt-4">
    <div class="card-header bg-body-secondary d-flex justify-content-between align-items-center">
        <strong>Action Priority Matrix</strong>
        <small class="text-muted">Impact (importance) &times; Effort (estimate)</small>
    </div>
    <div class="card-body">
        @foreach ($sections as $key => $meta)
            <div class="mb-4">
                <h6 class="mb-1 text-{{ $meta['class'] }}">
                    <i class="fa-solid {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                    <span class="badge rounded-pill text-bg-{{ $meta['class'] }}">{{ $matrix[$key]->count() }}</span>
                </h6>
                <p class="small text-muted mb-2">{{ $meta['hint'] }}</p>

                @forelse ($matrix[$key] as $tick)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                        <span class="text-truncate me-2">
                            <span class="text-{{ $tick->importance->class }}" title="Priority: {{ $tick->importance->name }}">
                                <i class="{{ $tick->importance->icon }}"></i>
                            </span>
                            <a href="/tickets/{{ $tick->id }}" class="text-decoration-none">#{{ $tick->id }} {{ $tick->subject }}</a>
                        </span>
                        <span class="badge text-bg-light text-nowrap">
                            {{ $tick->estimate > 0 ? rtrim(rtrim(number_format($tick->estimate, 2), '0'), '.') . 'h' : '—' }}
                        </span>
                    </div>
                @empty
                    <p class="small text-muted fst-italic mb-0">None</p>
                @endforelse
            </div>
        @endforeach
    </div>
</div>
