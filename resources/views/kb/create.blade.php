@extends('layouts.app')

@section('title', 'Create Article')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('kb.index') }}">Knowledge Base</a></li>
        <li class="breadcrumb-item active" aria-current="page">Create Article</li>
    </ol>
</nav>

<h1 class="mb-4">Create Article</h1>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('kb.store') }}">
    @csrf

    <div class="row">
        <div class="col-lg-8">
            {{-- Title --}}
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Body Markdown (EasyMDE) --}}
            <div class="mb-3">
                <label for="body_markdown" class="form-label">Content (Markdown)</label>
                <textarea name="body_markdown" id="body_markdown">{{ old('body_markdown') }}</textarea>
                @error('body_markdown')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Category --}}
            <div class="mb-3" x-data="{ adding: false, name: '', error: '', saving: false }">
                <label for="category_id" class="form-label">Category</label>
                <div class="d-flex gap-2">
                    <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                        <option value="">Select category...</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-outline-secondary btn-sm text-nowrap" @click="adding = !adding">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                @error('category_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror

                <div x-show="adding" x-cloak class="mt-2">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" placeholder="New category name..." x-model="name" @keydown.enter.prevent="
                            if (!name.trim() || saving) return;
                            saving = true; error = '';
                            fetch('{{ route('kb.categories.quick-store') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify({ name: name.trim() })
                            }).then(r => r.ok ? r.json() : r.json().then(d => Promise.reject(d)))
                            .then(cat => {
                                let sel = document.getElementById('category_id');
                                let opt = new Option(cat.name, cat.id, true, true);
                                sel.add(opt);
                                name = ''; adding = false;
                            })
                            .catch(e => { error = e.errors?.name?.[0] || e.message || 'Failed to create category'; })
                            .finally(() => { saving = false; });
                        ">
                        <button type="button" class="btn btn-success" :disabled="!name.trim() || saving" @click="
                            if (!name.trim() || saving) return;
                            saving = true; error = '';
                            fetch('{{ route('kb.categories.quick-store') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify({ name: name.trim() })
                            }).then(r => r.ok ? r.json() : r.json().then(d => Promise.reject(d)))
                            .then(cat => {
                                let sel = document.getElementById('category_id');
                                let opt = new Option(cat.name, cat.id, true, true);
                                sel.add(opt);
                                name = ''; adding = false;
                            })
                            .catch(e => { error = e.errors?.name?.[0] || e.message || 'Failed to create category'; })
                            .finally(() => { saving = false; });
                        ">
                            <span x-show="!saving">Add</span>
                            <span x-show="saving">...</span>
                        </button>
                    </div>
                    <div class="text-danger small mt-1" x-show="error" x-text="error"></div>
                </div>
            </div>

            {{-- Visibility --}}
            <div class="mb-3">
                <label for="visibility" class="form-label">Visibility</label>
                <select name="visibility" id="visibility" class="form-select @error('visibility') is-invalid @enderror" required>
                    <option value="internal" @selected(old('visibility') === 'internal')>Internal</option>
                    <option value="public" @selected(old('visibility') === 'public')>Public</option>
                    <option value="restricted" @selected(old('visibility') === 'restricted')>Restricted</option>
                </select>
                @error('visibility')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Tags --}}
            <div class="mb-3" x-data="{ adding: false, name: '', error: '', saving: false }">
                <label class="form-label">Tags</label>
                <div class="card card-body bg-body-tertiary" style="max-height: 200px; overflow-y: auto;" id="tags-container">
                    @foreach($tags as $tag)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tags[]"
                                   value="{{ $tag->id }}" id="tag_{{ $tag->id }}"
                                   @checked(is_array(old('tags')) && in_array($tag->id, old('tags')))>
                            <label class="form-check-label" for="tag_{{ $tag->id }}">{{ $tag->name }}</label>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm mt-1" @click="adding = !adding">
                    <i class="fas fa-plus"></i> Add Tag
                </button>
                <div x-show="adding" x-cloak class="mt-2">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" placeholder="New tag name..." x-model="name" @keydown.enter.prevent="
                            if (!name.trim() || saving) return;
                            saving = true; error = '';
                            fetch('{{ route('kb.tags.quick-store') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify({ name: name.trim() })
                            }).then(r => r.ok ? r.json() : r.json().then(d => Promise.reject(d)))
                            .then(tag => {
                                let container = document.getElementById('tags-container');
                                let div = document.createElement('div');
                                div.className = 'form-check';
                                let cb = document.createElement('input');
                                cb.className = 'form-check-input';
                                cb.type = 'checkbox';
                                cb.name = 'tags[]';
                                cb.value = tag.id;
                                cb.id = 'tag_' + tag.id;
                                cb.checked = true;
                                let lbl = document.createElement('label');
                                lbl.className = 'form-check-label';
                                lbl.htmlFor = 'tag_' + tag.id;
                                lbl.textContent = tag.name;
                                div.appendChild(cb);
                                div.appendChild(lbl);
                                container.appendChild(div);
                                name = ''; adding = false;
                            })
                            .catch(e => { error = e.errors?.name?.[0] || e.message || 'Failed to create tag'; })
                            .finally(() => { saving = false; });
                        ">
                        <button type="button" class="btn btn-success" :disabled="!name.trim() || saving" @click="
                            if (!name.trim() || saving) return;
                            saving = true; error = '';
                            fetch('{{ route('kb.tags.quick-store') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify({ name: name.trim() })
                            }).then(r => r.ok ? r.json() : r.json().then(d => Promise.reject(d)))
                            .then(tag => {
                                let container = document.getElementById('tags-container');
                                let div = document.createElement('div');
                                div.className = 'form-check';
                                let cb = document.createElement('input');
                                cb.className = 'form-check-input';
                                cb.type = 'checkbox';
                                cb.name = 'tags[]';
                                cb.value = tag.id;
                                cb.id = 'tag_' + tag.id;
                                cb.checked = true;
                                let lbl = document.createElement('label');
                                lbl.className = 'form-check-label';
                                lbl.htmlFor = 'tag_' + tag.id;
                                lbl.textContent = tag.name;
                                div.appendChild(cb);
                                div.appendChild(lbl);
                                container.appendChild(div);
                                name = ''; adding = false;
                            })
                            .catch(e => { error = e.errors?.name?.[0] || e.message || 'Failed to create tag'; })
                            .finally(() => { saving = false; });
                        ">
                            <span x-show="!saving">Add</span>
                            <span x-show="saving">...</span>
                        </button>
                    </div>
                    <div class="text-danger small mt-1" x-show="error" x-text="error"></div>
                </div>
            </div>

            {{-- Commit Message & Submit --}}
            <div class="mb-3">
                <label for="commit_message" class="form-label">Commit Message</label>
                <input type="text" name="commit_message" id="commit_message"
                       class="form-control @error('commit_message') is-invalid @enderror"
                       value="{{ old('commit_message', 'Initial version') }}" required>
                @error('commit_message')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i> Create Article
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    new EasyMDE({
        element: document.getElementById('body_markdown'),
        spellChecker: false,
        autosave: { enabled: true, uniqueId: 'kb-create', delay: 5000 },
        minHeight: '350px',
        placeholder: 'Write your article content in Markdown...',
        toolbar: [
            'bold', 'italic', 'heading', '|',
            'quote', 'unordered-list', 'ordered-list', 'checklist', '|',
            'link', 'image', 'code', 'table', '|',
            'preview', 'side-by-side', 'fullscreen', '|',
            'guide'
        ],
        status: ['lines', 'words'],
    });
});
</script>
@endpush
