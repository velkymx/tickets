@extends($layout ?? 'layouts.app')
@section('content')
@foreach($articles as $article)
<div>{{ $article->title }}</div>
@endforeach
@endsection
