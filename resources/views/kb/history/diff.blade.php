@extends('layouts.app')

@section('title', 'Diff: v' . $fromVersion->version_number . ' → v' . $toVersion->version_number)

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('kb.index') }}">Knowledge Base</a></li>
        <li class="breadcrumb-item"><a href="{{ route('kb.show', $article->slug) }}">{{ $article->title }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('kb.history', $article->slug) }}">History</a></li>
        <li class="breadcrumb-item active" aria-current="page">Diff</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Diff: v{{ $fromVersion->version_number }} &rarr; v{{ $toVersion->version_number }}</h1>
    <a href="{{ route('kb.history', $article->slug) }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> All Versions
    </a>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="card border-danger">
            <div class="card-header bg-danger-subtle">
                <strong>v{{ $fromVersion->version_number }}</strong> &mdash; {{ $fromVersion->commit_message }}
                <div class="small text-muted">{{ $fromVersion->created_at->format('M jS, Y g:ia') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-success">
            <div class="card-header bg-success-subtle">
                <strong>v{{ $toVersion->version_number }}</strong> &mdash; {{ $toVersion->commit_message }}
                <div class="small text-muted">{{ $toVersion->created_at->format('M jS, Y g:ia') }}</div>
            </div>
        </div>
    </div>
</div>

@php
    $fromLines = explode("\n", $fromVersion->body_markdown);
    $toLines = explode("\n", $toVersion->body_markdown);
@endphp

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-danger-subtle">
                <strong>v{{ $fromVersion->version_number }}</strong>
            </div>
            <div class="card-body">
                <pre class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;"><code>{{ $fromVersion->body_markdown }}</code></pre>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-success-subtle">
                <strong>v{{ $toVersion->version_number }}</strong>
            </div>
            <div class="card-body">
                <pre class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;"><code>{{ $toVersion->body_markdown }}</code></pre>
            </div>
        </div>
    </div>
</div>
@endsection
