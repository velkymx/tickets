@extends($layout ?? 'layouts.app')
@section('title', $article->title)
@section('content')
<h1>{{ $article->title }}</h1>
{!! $article->body_html !!}
@endsection
