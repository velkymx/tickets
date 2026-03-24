@extends($layout ?? 'layouts.app')
@section('content')
<h1>{{ $category->name }}</h1>
@foreach($articles as $article)
<div>{{ $article->title }}</div>
@endforeach
@endsection
