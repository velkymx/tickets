@extends('layouts.app')
@section('content')
<h1>Manage Tags</h1>
@foreach($tags as $tag)
<div>{{ $tag->name }} ({{ $tag->articles_count }})</div>
@endforeach
@endsection
