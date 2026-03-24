<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if(auth()->check() && auth()->user()->theme !== 'auto') data-bs-theme="{{ auth()->user()->theme === 'darkly' ? 'dark' : 'light' }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Welcome') | Tickets!</title>

    <link rel="icon" type="image/png" sizes="16x16" href="/tickets.png">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body id="app-layout">
<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="/tickets.png" class="img-fluid" style="max-height: 32px;"> <span class="fw-bold">Tickets</span>
        </a>

        <button class="navbar-toggler" type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" 
                aria-expanded="false" 
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"><img src="/tickets.png" alt="Tickets"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="{{ url('/home') }}">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/tickets') }}">All Tickets</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/milestone') }}">Milestones</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/releases') }}">Releases</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/projects') }}">Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('kb.index') }}">Knowledge Base</a></li>
            </ul>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                @guest
                    <li class="nav-item"><a class="nav-link" href="{{ url('/login') }}">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/register') }}">Register</a></li>
                @else
                    @php
                        $latestNotifications = Auth::user()->notifications()->latest()->limit(5)->get();
                        $unreadNotificationCount = Auth::user()->unreadNotifications()->count();
                    @endphp

                    <li class="nav-item"><a class="nav-link" href="/ticket/create">New Ticket</a></li>
                    <li class="nav-item"><a class="nav-link" href="/tickets/import">Import</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-inline-flex align-items-center gap-2" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="{{ $unreadNotificationCount > 0 ? 'fas fa-bell' : 'far fa-bell' }}" aria-hidden="true"></i>
                            <span class="visually-hidden">Notifications</span>
                            @if ($unreadNotificationCount > 0)
                                <span class="badge rounded-pill bg-danger notification-count-inline">
                                    {{ $unreadNotificationCount }}
                                </span>
                            @endif
                        </a>

                        <div class="dropdown-menu dropdown-menu-end p-0 overflow-hidden" aria-labelledby="notificationsDropdown" style="min-width: 22rem;">
                            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                <strong>Notifications</strong>
                                @if ($unreadNotificationCount > 0)
                                    <span class="badge bg-danger-subtle text-danger">{{ $unreadNotificationCount }} unread</span>
                                @endif
                            </div>

                            @forelse ($latestNotifications as $notification)
                                <a class="dropdown-item py-3 border-bottom" href="{{ $notification->data['url'] ?? '/activity' }}">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div class="small">
                                            <div class="fw-semibold">{{ $notification->data['excerpt'] ?? $notification->data['message'] ?? 'Activity update' }}</div>
                                            <div class="text-muted">{{ $notification->created_at->diffForHumans() }}</div>
                                        </div>
                                        @if (! $notification->read_at)
                                            <span class="text-danger">●</span>
                                        @endif
                                    </div>
                                </a>
                            @empty
                                <div class="px-3 py-4 text-muted small">No recent notifications.</div>
                            @endforelse

                            <div class="px-3 py-2 bg-body-tertiary">
                                <a class="small fw-semibold text-decoration-none" href="/activity">View all</a>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ Auth::user()->name }}
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="/users/{{ Auth::id() }}">Profile</a></li>
                            <li><a class="dropdown-item" href="/user/edit">Edit Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                             document.getElementById('logout-form').submit();">
                                    Logout
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                        </ul>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>
    
    <main class="container py-4">
        
        {{-- Session messages using Bootstrap 5 Dismissible Alerts --}}
        @if (Session::has('info_message'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                {{ Session::get('info_message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (Session::has('error_message'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ Session::get('error_message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>
    
    <footer class="container text-center py-3 border-top">
        <p class="text-muted mb-0">
            Tickets is the open source ticket tracker powered by <a href="https://laravel.com" target="_blank">Laravel</a>.
            <a href="https://github.com/velkymx/tickets" target="_blank">Check us out on Github.</a> Provided "as is" under the
            <a href="https://opensource.org/licenses/MIT" target="_blank">MIT License</a>
        </p>
    </footer>

    @yield('javascript')
</body>
</html>
