@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h1>Activity Center</h1>

        @foreach ($notifications as $notification)
            <article class="activity-item">
                <p>{{ $notification->data['excerpt'] ?? $notification->data['message'] ?? '' }}</p>
            </article>
        @endforeach
    </div>
@endsection
