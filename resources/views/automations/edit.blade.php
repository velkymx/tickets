@extends('layouts.app')

@section('title', 'Edit Automation')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('automations.index') }}">Automations</a></li>
        <li class="breadcrumb-item"><a href="{{ route('automations.show', $automation) }}">{{ $automation->name }}</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
</nav>

<h1 class="mb-4">Edit: {{ $automation->name }}</h1>

<form method="POST" action="{{ route('automations.update', $automation) }}">
    @csrf
    @method('PUT')

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-body-secondary"><strong>General</strong></div>
        <div class="card-body">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" name="name" id="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $automation->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" rows="2" class="form-control">{{ old('description', $automation->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label for="trigger" class="form-label">Trigger</label>
                <select name="trigger" id="trigger" class="form-select">
                    @foreach($triggers as $key => $trigger)
                        <option value="{{ $key }}" @selected(old('trigger', $automation->trigger) === $key)>{{ $trigger['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-4">
                <div class="form-check">
                    <input type="checkbox" name="enabled" id="enabled" class="form-check-input" value="1"
                           @checked(old('enabled', $automation->enabled))>
                    <label class="form-check-label" for="enabled">Enabled</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="stop_after_match" id="stop_after_match" class="form-check-input" value="1"
                           @checked(old('stop_after_match', $automation->stop_after_match))>
                    <label class="form-check-label" for="stop_after_match">Stop after first match</label>
                </div>
            </div>
        </div>
    </div>

    @php
        $firstRule = $automation->rules->first();
        $existingConditions = $firstRule?->conditions_json ?? ['all' => []];
        $existingActions = $firstRule?->actions->map(fn($a) => ['type' => $a->type, 'config' => $a->config_json])->toArray() ?? [];
    @endphp

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-body-secondary"><strong>Rule</strong></div>
        <div class="card-body">
            <h6 class="fw-semibold mb-2">Conditions</h6>
            @include('automations.partials.condition-builder', [
                'conditionsJson' => $existingConditions,
                'fields' => [],
                'operators' => $operators,
            ])

            <h6 class="fw-semibold mt-4 mb-2">Actions</h6>
            @include('automations.partials.action-builder', [
                'actionsJson' => $existingActions,
                'actionTypes' => $actionTypes,
            ])
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('automations.show', $automation) }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Automation</button>
    </div>
</form>
@endsection
