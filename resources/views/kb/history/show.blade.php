@extends('layouts.app')
@section('content')
<h1>Version {{ $versionModel->version_number }}: {{ $versionModel->title }}</h1>
{!! $versionModel->body_html !!}
@endsection
