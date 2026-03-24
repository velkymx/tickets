@extends('layouts.app')
@section('content')
<h1>Create Article</h1>
<form method="POST" action="{{ route('kb.store') }}">
    @csrf
    <input type="text" name="title">
    <textarea name="body_markdown"></textarea>
    <select name="category_id">
        @foreach($categories as $category)
        <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
    </select>
    <select name="visibility">
        <option value="internal">Internal</option>
        <option value="public">Public</option>
        <option value="restricted">Restricted</option>
    </select>
    <input type="text" name="commit_message">
    @foreach($tags as $tag)
    <label><input type="checkbox" name="tags[]" value="{{ $tag->id }}"> {{ $tag->name }}</label>
    @endforeach
    <button type="submit">Create</button>
</form>
@endsection
