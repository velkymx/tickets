@props(['users', 'selected' => []])

<input type="hidden" name="permitted_users_present" value="1">

@php
    $oldPresent = (bool) old('permitted_users_present', false);
    $selectedIds = array_map('intval', (array) old('permitted_users', $oldPresent ? [] : $selected));
@endphp

<div class="mb-3">
    <label for="permitted_users" class="form-label">Permitted Users</label>
    <select name="permitted_users[]" id="permitted_users" class="form-select @error('permitted_users') is-invalid @enderror @error('permitted_users.*') is-invalid @enderror" multiple size="6">
        @foreach ($users as $user)
            <option value="{{ $user->id }}" @selected(in_array((int) $user->id, $selectedIds, true))>{{ $user->name }}</option>
        @endforeach
    </select>
    <div class="form-text">Only applies to restricted articles.</div>
    @error('permitted_users')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('permitted_users.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
