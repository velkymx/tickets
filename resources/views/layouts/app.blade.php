<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>@yield('title', 'Welcome') | Tickets!</title>

    <link rel="icon" type="image/png" sizes="16x16" href="/tickets.png">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/yeti/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    @auth
    @if (Auth::user()->theme && (strtolower(Auth::user()->theme) == '/css/bootstrap.darkly.min.css'))
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/darkly/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    @endif
    @endauth
</head>
<body id="app-layout">
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            <span class="text-primary">Tickets</span>
        </a>

        {{-- Toggler Button: data-bs-target now points to the new ID --}}
        <button class="navbar-toggler" type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#navbarSupportedContent" {{-- CORRECTED ID --}}
                aria-controls="navbarSupportedContent" 
                aria-expanded="false" 
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Collapsable Content: The ID must match the data-bs-target --}}
        <div class="collapse navbar-collapse" id="navbarSupportedContent"> {{-- CORRECTED ID --}}
            
            {{-- Left Side: Navigation Links (me-auto pushes it left) --}}
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="{{ url('/home') }}">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/tickets') }}">All Tickets</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/milestone') }}">Milestones</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/releases') }}">Releases</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/projects') }}">Projects</a></li>
            </ul>

            {{-- Right Side: Authentication/User Links (ms-auto pushes it right) --}}
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                @guest
                    <li class="nav-item"><a class="nav-link" href="{{ url('/login') }}">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/register') }}">Register</a></li>
                @else
                    <li class="nav-item"><a class="nav-link" href="/ticket/create">New Ticket</a></li>
                    <li class="nav-item"><a class="nav-link" href="/tickets/import">Import</a></li>
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

    {{-- Keep jQuery for legacy code (jquery-ui/jquery-validate) if necessary, 
         but Bootstrap 5.3 does not require it. Load it first if needed. --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    
    @yield('javascript')
</body>
</html>