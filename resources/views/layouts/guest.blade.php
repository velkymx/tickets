<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Tickets') }}</title>

    <link rel="icon" type="image/png" sizes="16x16" href="/tickets.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @vite(['resources/css/theme.css'])
</head>
<body class="d-flex flex-column min-vh-100">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}">
                        <h1 class="text-primary">Tickets</h1>
                    </a>
                </div>

                <div class="card shadow">
                    <div class="card-body p-4">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="container text-center py-3 mt-auto border-top">
        <p class="text-muted mb-0 small">
            <a href="https://github.com/velkymx/tickets" target="_blank">Tickets</a> powered by Laravel
        </p>
    </footer>
</body>
</html>
