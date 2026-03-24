@extends('layouts.app')
@section('content')
<h1>Diff: v{{ $fromVersion->version_number }} → v{{ $toVersion->version_number }}</h1>
<div>{{ $fromVersion->body_markdown }}</div>
<div>{{ $toVersion->body_markdown }}</div>
@endsection
