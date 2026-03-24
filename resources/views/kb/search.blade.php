@extends($layout ?? 'layouts.app')
@section('content')
<h1>Search: {{ $query }}</h1>
@foreach($articles as $article)
<div>{{ $article->title }}</div>
@endforeach
@endsection
