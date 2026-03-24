@extends('layouts.app')
@section('content')
<h1>Edit: {{ $article->title }}</h1>
<form method="POST" action="{{ route('kb.update', $article->slug) }}">
    @csrf
    @method('PUT')
    <input type="text" name="title" value="{{ $article->title }}">
    <textarea name="body_markdown">{{ $article->body_markdown }}</textarea>
    <select name="category_id">
        @foreach($categories as $category)
        <option value="{{ $category->id }}" @selected($category->id == $article->category_id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <select name="visibility">
        <option value="internal" @selected($article->visibility === 'internal')>Internal</option>
        <option value="public" @selected($article->visibility === 'public')>Public</option>
        <option value="restricted" @selected($article->visibility === 'restricted')>Restricted</option>
    </select>
    <input type="text" name="commit_message">
    @foreach($tags as $tag)
    <label><input type="checkbox" name="tags[]" value="{{ $tag->id }}" @checked($article->tags->contains($tag))> {{ $tag->name }}</label>
    @endforeach
    <button type="submit">Update</button>
</form>
@endsection
