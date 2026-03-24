@extends('layouts.app')
@section('content')
<h1>Trashed Articles</h1>
@foreach($articles as $article)
<div>{{ $article->title }}</div>
@endforeach
@endsection
