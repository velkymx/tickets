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

<form method="POST" action="{{ route('kb.update', $article->slug) }}" x-data="{ preview: false }">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-lg-8">
            {{-- Title --}}
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title', $article->title) }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Body Markdown --}}
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label for="body_markdown" class="form-label mb-0">Content (Markdown)</label>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="preview = !preview">
                        <span x-show="!preview"><i class="fas fa-eye"></i> Preview</span>
                        <span x-show="preview" x-cloak><i class="fas fa-edit"></i> Edit</span>
                    </button>
                </div>

                <div x-show="!preview">
                    <textarea name="body_markdown" id="body_markdown"
                              class="form-control font-monospace @error('body_markdown') is-invalid @enderror"
                              rows="15" required>{{ old('body_markdown', $article->body_markdown) }}</textarea>
                    @error('body_markdown')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div x-show="preview" x-cloak class="card card-body bg-body-tertiary" style="min-height: 200px;">
                    <p class="text-muted">Markdown preview is not available in this view. Save to see rendered output.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Category --}}
            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                    <option value="">Select category...</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $article->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
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

            {{-- Tags --}}
            <div class="mb-3">
                <label class="form-label">Tags</label>
                <div class="card card-body bg-body-tertiary" style="max-height: 200px; overflow-y: auto;">
                    @foreach($tags as $tag)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tags[]"
                                   value="{{ $tag->id }}" id="tag_{{ $tag->id }}"
                                   @checked(old('tags') ? in_array($tag->id, old('tags')) : $article->tags->contains($tag))>
                            <label class="form-check-label" for="tag_{{ $tag->id }}">{{ $tag->name }}</label>
                        </div>
                    @endforeach
                </div>
            </div>

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
