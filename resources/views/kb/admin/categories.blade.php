@extends('layouts.app')
@section('content')
<h1>Manage Categories</h1>
@foreach($categories as $category)
<div>{{ $category->name }} ({{ $category->articles_count }})</div>
@endforeach
@endsection
