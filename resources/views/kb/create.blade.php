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
        <div class="col-lg-9">
            {{-- Title --}}
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="body_markdown" class="form-label">Content (Markdown)</label>
                <textarea name="body_markdown" id="body_markdown" data-markdown-editor data-autosave-id="kb-create" data-placeholder="Write your article content in Markdown...">{{ old('body_markdown') }}</textarea>
                @error('body_markdown')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-lg-3">
            {{-- Category --}}
            @include('kb.partials.quick-store-field', [
                'label' => 'Category',
                'mode' => 'select',
                'storeUrl' => route('kb.categories.quick-store'),
                'errorLabel' => 'Failed to create category',
                'inputPlaceholder' => 'New category name...',
                'selectId' => 'category_id',
                'fieldName' => 'category_id',
                'selectRequired' => true,
                'placeholderOption' => 'Select category...',
                'selectedId' => old('category_id'),
            ])

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
            @include('kb.partials.quick-store-field', [
                'label' => 'Tags',
                'mode' => 'checklist',
                'storeUrl' => route('kb.tags.quick-store'),
                'errorLabel' => 'Failed to create tag',
                'inputPlaceholder' => 'New tag name...',
                'inviteLabel' => 'Add Tag',
                'tags' => $tags,
                'checkedIds' => is_array(old('tags')) ? old('tags') : [],
            ])

            <x-permitted-users-field :users="$users" :selected="[]" />

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
