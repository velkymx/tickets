@props([
    'viewfilters',
    'filter',
    'action' => null,
])
@php
    $action = $action ?? url()->current();
    // Preserve sort + active tab across a filter submit (they aren't form fields).
    $carry = array_filter([
        'sort' => request('sort'),
        'dir' => request('dir'),
        'assignee' => request('assignee'),
        'importance_id' => request('importance_id'),
    ], fn ($v) => $v !== null && $v !== '');
@endphp

<form method="GET" action="{{ $action }}" class="mb-4">
    @foreach ($carry as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endforeach

    <div class="row g-2 align-items-end">
        <div class="col-auto">
            <span class="btn btn-outline-secondary disabled">Filter Tickets</span>
        </div>

        <div class="col-md-3 col-lg-3">
            <label for="q" class="form-label visually-hidden">Search</label>
            <input type="text" placeholder="Search" class="form-control" name="q" id="q" value="{{ request('q') }}">
        </div>

        <div class="col-md-auto">
            <label for="perpage" class="form-label visually-hidden"># Rows</label>
            <select name="perpage" id="perpage" class="form-select">
                <option value="" @selected(request('perpage') == '')># Rows</option>
                @foreach ([10, 20, 30, 40, 50] as $n)
                    <option value="{{ $n }}" @selected(request('perpage') == $n)>{{ $n }} Rows</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-auto">
            <label for="status_id" class="form-label visually-hidden">Status</label>
            <select name="status_id" id="status_id" class="form-select">
                @foreach ($viewfilters['statuses'] as $id => $name)
                    <option value="{{ $id }}" @selected(($filter['status_id'] ?? null) == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-auto">
            <label for="milestone_id" class="form-label visually-hidden">Milestone</label>
            <select name="milestone_id" id="milestone_id" class="form-select">
                @foreach ($viewfilters['milestones'] as $id => $name)
                    <option value="{{ $id }}" @selected(($filter['milestone_id'] ?? null) == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-auto">
            <label for="type_id" class="form-label visually-hidden">Type</label>
            <select name="type_id" id="type_id" class="form-select">
                @foreach ($viewfilters['types'] as $id => $name)
                    <option value="{{ $id }}" @selected(($filter['type_id'] ?? null) == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Refresh Rows</button>
        </div>
    </div>
</form>
