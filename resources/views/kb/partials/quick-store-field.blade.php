@php
$label ??= '';
$mode ??= 'select';
$storeUrl ??= '#';
$errorLabel ??= 'Failed to create item';
$inputPlaceholder ??= 'New item name...';
$selectId ??= 'category_id';
$fieldName ??= 'category_id';
$selectRequired ??= false;
$placeholderOption ??= 'Select...';
$selectedId ??= null;
$tags ??= [];
$checkedIds ??= [];
$inviteLabel ??= 'Add';
@endphp
<div class="mb-3" x-data="{
    adding: false,
    name: '',
    error: '',
    saving: false,
    save() {
        if (!this.name.trim() || this.saving) return;
        const itemName = this.name.trim();
        this.saving = true;
        this.error = '';
        fetch('{{ $storeUrl }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ name: itemName })
        }).then(r => r.ok ? r.json() : r.json().then(d => Promise.reject(d)))
        .then(item => {
            @if ($mode === 'select')
                document.getElementById('{{ $selectId }}').add(new Option(item.name, item.id, true, true));
            @else
                const container = document.getElementById('tags-container');
                const div = document.createElement('div');
                div.className = 'form-check';
                const cb = document.createElement('input');
                cb.className = 'form-check-input';
                cb.type = 'checkbox';
                cb.name = 'tags[]';
                cb.value = item.id;
                cb.id = 'tag_' + item.id;
                cb.checked = true;
                const lbl = document.createElement('label');
                lbl.className = 'form-check-label';
                lbl.htmlFor = 'tag_' + item.id;
                lbl.textContent = item.name;
                div.appendChild(cb);
                div.appendChild(lbl);
                container.appendChild(div);
            @endif
            this.name = '';
            this.adding = false;
        })
        .catch(e => { this.error = e.errors?.name?.[0] || e.message || '{{ $errorLabel }}'; })
        .finally(() => { this.saving = false; });
    }
}">
    @if ($mode === 'select')
        <label for="{{ $selectId }}" class="form-label">{{ $label }}</label>
        <div class="d-flex gap-2">
            <select name="{{ $fieldName }}" id="{{ $selectId }}" class="form-select @error($fieldName) is-invalid @enderror" @if($selectRequired) required @endif>
                <option value="">{{ $placeholderOption }}</option>
                @include('kb.partials.category-options', ['parentId' => null, 'depth' => 0, 'selectedId' => $selectedId])
            </select>
            <button type="button" class="btn btn-outline-secondary btn-sm text-nowrap" @click="adding = !adding">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        @error($fieldName)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    @else
        <label class="form-label">{{ $label }}</label>
        <div class="card card-body bg-body-tertiary" style="max-height: 200px; overflow-y: auto;" id="tags-container">
            @foreach ($tags as $tag)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="tags[]"
                           value="{{ $tag->id }}" id="tag_{{ $tag->id }}"
                           @checked(in_array($tag->id, $checkedIds))>
                    <label class="form-check-label" for="tag_{{ $tag->id }}">{{ $tag->name }}</label>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm mt-1" @click="adding = !adding">
            <i class="fas fa-plus"></i> {{ $inviteLabel }}
        </button>
    @endif

    <div x-show="adding" x-cloak class="mt-2">
        <div class="input-group input-group-sm">
            <input type="text" class="form-control" placeholder="{{ $inputPlaceholder }}" x-model="name" @keydown.enter.prevent="save()">
            <button type="button" class="btn btn-success" :disabled="!name.trim() || saving" @click="save()">
                <span x-show="!saving">Add</span>
                <span x-show="saving">...</span>
            </button>
        </div>
        <div class="text-danger small mt-1" x-show="error" x-text="error"></div>
    </div>
</div>
