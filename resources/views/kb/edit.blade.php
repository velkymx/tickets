@extends('layouts.app')

@section('title', 'Edit: ' . $article->title)

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('kb.index') }}">Knowledge Base</a></li>
        @if($article->category)
            <li class="breadcrumb-item"><a href="{{ route('kb.category', $article->category->slug) }}">{{ $article->category->name }}</a></li>
        @endif
        <li class="breadcrumb-item"><a href="{{ route('kb.show', $article->slug) }}">{{ $article->title }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </ol>
</nav>

<h1 class="mb-4">Edit: {{ $article->title }}</h1>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('kb.update', $article->slug) }}">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-lg-9">
            {{-- Title --}}
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title', $article->title) }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="body_markdown" class="form-label">Content (Markdown)</label>
                <textarea name="body_markdown" id="body_markdown" data-markdown-editor data-autosave-id="kb-edit-{{ $article->slug }}">{{ old('body_markdown', $article->body_markdown) }}</textarea>
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
                'selectedId' => old('category_id', $article->category_id),
            ])

            {{-- Status --}}
            @if($canManageAccess)
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="draft" @selected(old('status', $article->status) === 'draft')>Draft</option>
                        <option value="verified" @selected(old('status', $article->status) === 'verified')>Verified</option>
                        <option value="deprecated" @selected(old('status', $article->status) === 'deprecated')>Deprecated</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Visibility --}}
                <div class="mb-3">
                    <label for="visibility" class="form-label">Visibility</label>
                    <select name="visibility" id="visibility" class="form-select @error('visibility') is-invalid @enderror" required>
                        <option value="internal" @selected(old('visibility', $article->visibility) === 'internal')>Internal</option>
                        <option value="public" @selected(old('visibility', $article->visibility) === 'public')>Public</option>
                        <option value="restricted" @selected(old('visibility', $article->visibility) === 'restricted')>Restricted</option>
                    </select>
                    @error('visibility')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            @else
                <input type="hidden" name="status" value="{{ $article->status }}">
                <input type="hidden" name="visibility" value="{{ $article->visibility }}">

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <p class="form-control-plaintext mb-0">{{ ucfirst($article->status) }}</p>
                </div>

                <div class="mb-3">
                    <label class="form-label">Visibility</label>
                    <p class="form-control-plaintext mb-0">{{ ucfirst($article->visibility) }}</p>
                </div>
            @endif

            {{-- Tags --}}
            @include('kb.partials.quick-store-field', [
                'label' => 'Tags',
                'mode' => 'checklist',
                'storeUrl' => route('kb.tags.quick-store'),
                'errorLabel' => 'Failed to create tag',
                'inputPlaceholder' => 'New tag name...',
                'inviteLabel' => 'Add Tag',
                'tags' => $tags,
                'checkedIds' => old('tags', $article->tags->pluck('id')->toArray()),
            ])

            @if($canManageAccess)
                <x-permitted-users-field :users="$users" :selected="$article->permissions->pluck('user_id')->all()" />
            @endif

            {{-- Commit Message & Submit --}}
            <div class="mb-3">
                <label for="commit_message" class="form-label">Commit Message</label>
                <input type="text" name="commit_message" id="commit_message"
                       class="form-control @error('commit_message') is-invalid @enderror"
                       value="{{ old('commit_message') }}" placeholder="Describe your changes..." required>
                @error('commit_message')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i> Update Article
                </button>
                <a href="{{ route('kb.show', $article->slug) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>

            @can('delete', $article)
                <hr>
                <form method="POST" action="{{ route('kb.destroy', $article->slug) }}" onsubmit="return confirm('Are you sure you want to trash this article?')">
                    @csrf
                    @method('DELETE')
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-trash me-1"></i> Move to Trash
                        </button>
                    </div>
                </form>
            @endcan
        </div>
    </div>
</form>
@endsection
