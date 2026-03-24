<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Knowledge Base') | Tickets!</title>

    <link rel="icon" type="image/png" sizes="16x16" href="/tickets.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ route('kb.index') }}">
            <img src="/tickets.png" class="img-fluid" style="max-height: 32px;">
            <span class="fw-bold">Knowledge Base</span>
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#kbNavbar"
                aria-controls="kbNavbar"
                aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="kbNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('kb.index') }}">Articles</a>
                </li>
            </ul>

            <form class="d-flex me-3" action="{{ route('kb.search') }}" method="GET">
                <input class="form-control form-control-sm" type="search" name="q" placeholder="Search articles..." value="{{ request('q') }}">
            </form>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/login') }}">Login</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    @if (Session::has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ Session::get('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (Session::has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ Session::get('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @yield('content')
</main>

<footer class="container text-center py-3 border-top">
    <p class="text-muted mb-0">
        Powered by <a href="https://github.com/velkymx/tickets" target="_blank">Tickets</a> &middot;
        <a href="{{ url('/') }}">Home</a>
    </p>
</footer>

@yield('javascript')
</body>
</html>
