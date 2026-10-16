@extends('layouts.app')
@section('title', 'Login')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header text-bg-primary text-center rounded-top-3">
                    <h2 class="h4 mb-0">Log In</h2>
                </div>
                <div class="card-body p-4">
                    @php($ssoProviders = \App\Http\Controllers\Auth\SsoController::enabledProviders())
                    @if(count($ssoProviders) > 0)
                        <div class="d-grid gap-2 mb-3">
                            @foreach($ssoProviders as $provider)
                                <a href="{{ route('sso.redirect', $provider) }}" class="btn btn-outline-secondary">
                                    Continue with {{ ucfirst($provider === 'azure' ? 'Microsoft' : $provider) }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if(config('auth.password_login_enabled'))
                    
                    {{-- Session Status Display --}}
                    @if (session('status'))
                        <div class="alert alert-success mb-3" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        {{-- Email Address Field --}}
                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('Email') }}</label>
                            <input id="email" type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required autofocus autocomplete="username">
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Password Field --}}
                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Password') }}</label>
                            <input id="password" type="password" 
                                   class="form-control @error('password') is-invalid @enderror"
                                   name="password"
                                   required autocomplete="current-password">
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Remember Me Checkbox --}}
                        <div class="mb-4 form-check">
                            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                            <label for="remember_me" class="form-check-label text-muted small">{{ __('Remember me') }}</label>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('password.request') }}" class="small">Forgot your password?</a>
                            <button type="submit" class="btn btn-primary">
                                {{ __('Log in') }}
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection