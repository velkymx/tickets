@extends('layouts.app')
@section('title', 'Edit User')
@section('content')
<h1 class="mb-4">Edit Profile</h1>
<hr>

<form method="POST" action="/user/update" id="user_form">
    @csrf 

    <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name', $user->name) }}" placeholder="Full Name" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Job Title</label>
        <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" id="title" value="{{ old('title', $user->title) }}" placeholder="Web Developer" required>
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="bio" class="form-label">Bio</label>
        <input type="hidden" name="bio" id="bio-input" value="{{ old('bio', $user->bio) }}">
        <div data-rich-text data-target="#bio-input" data-placeholder="Write a short bio about yourself here..."></div>
        @error('bio')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <h3 class="mt-5 mb-3">Contact Info</h3>
    
    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" value="{{ old('email', $user->email) }}" placeholder="Email" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="mb-3">
        <label for="phone" class="form-label">Phone Number</label>
        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="(343) 334-3423">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="timezone" class="form-label">Timezone</label>
        <select name="timezone" id="timezone" class="form-select @error('timezone') is-invalid @enderror">
            @foreach ($timezones as $timezone)
                @foreach($timezone as $key => $value)      
                <option value="{{ $key }}" @if (old('timezone', $user->timezone) == $key) selected @endif>{{ $value }}</option>
            @endforeach
            @endforeach
        </select>
        @error('timezone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="mb-3">
        <label for="theme" class="form-label">Theme</label>
        <select name="theme" id="theme" class="form-select @error('theme') is-invalid @enderror">
            @foreach ($themes as $key => $value)
                <option value="{{ $key }}" @if (old('theme', $user->theme) == $key) selected @endif>{{ $value }}</option>
            @endforeach
        </select>
        @error('theme')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <span class="form-label">Notify me about</span>
        @foreach (['notify_mentions' => 'Mentions', 'notify_replies' => 'Replies', 'notify_watching' => 'Watched tickets'] as $field => $label)
            <div class="form-check">
                {{-- Hidden 0 first: PHP keeps the last value per key, so checked yields 1 and unchecked yields 0. --}}
                <input type="hidden" name="{{ $field }}" value="0">
                <input type="checkbox" name="{{ $field }}" id="{{ $field }}" value="1" class="form-check-input" @checked(old($field, $user->{$field} ?? true))>
                <label for="{{ $field }}" class="form-check-label">{{ $label }}</label>
            </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-start mt-4">
        <button type="submit" class="btn btn-success">Save Profile</button>
    </div>
</form>

<h3 class="mt-5 mb-3">API Token</h3>
<p class="text-muted">Generate a bearer token to authenticate API requests. <a href="https://github.com/velkymx/tickets/blob/master/README.md" target="_blank">View API documentation</a> or <a href="{{ route('api.docs') }}">browse the interactive API docs</a>.</p>

@if (session('api_token_plain'))
    <div class="alert alert-warning">
        <strong>Copy your token now.</strong> It will not be shown again.
        <div class="mt-2">
            <code class="user-select-all">{{ session('api_token_plain') }}</code>
        </div>
    </div>
@endif

@if ($user->api_token)
    <div class="alert alert-success">
        <i class="fas fa-key me-1"></i> You have an active API token.
    </div>
    <form method="POST" action="{{ route('user.api-token.revoke') }}" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">Revoke Token</button>
    </form>
    <form method="POST" action="{{ route('user.api-token') }}" class="d-inline ms-2">
        @csrf
        <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('This will replace your existing token. Continue?')">Regenerate Token</button>
    </form>
@else
    <form method="POST" action="{{ route('user.api-token') }}">
        @csrf
        <button type="submit" class="btn btn-primary">Generate API Token</button>
    </form>
@endif
@endsection