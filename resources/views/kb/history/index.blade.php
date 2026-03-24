@extends('layouts.app')

@section('title', 'History: ' . $article->title)

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('kb.index') }}">Knowledge Base</a></li>
        <li class="breadcrumb-item"><a href="{{ route('kb.show', $article->slug) }}">{{ $article->title }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">History</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">History: {{ $article->title }}</h1>
    <a href="{{ route('kb.show', $article->slug) }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back to Article
    </a>
</div>

@include('kb.partials.version-list')
@endsection
