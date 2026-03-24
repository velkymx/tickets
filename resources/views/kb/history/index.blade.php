@extends('layouts.app')
@section('content')
<h1>History: {{ $article->title }}</h1>
@foreach($versions as $version)
<div>v{{ $version->version_number }} — {{ $version->commit_message }}</div>
@endforeach
@endsection
