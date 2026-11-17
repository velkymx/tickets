@props(['users', 'selected' => []])

<div class="mb-3">
    <label for="permitted_users" class="form-label">Allowed Users</label>
    <select name="permitted_users[]" id="permitted_users" class="form-select" multiple size="6">
        @foreach ($users as $user)
            <option value="{{ $user->id }}" @selected(in_array($user->id, old('permitted_users', $selected), true))>{{ $user->name }}</option>
        @endforeach
    </select>
    @error('permitted_users')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('permitted_users.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
