<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Knowledge Base')</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <div class="container py-4">
        @yield('content')
    </div>
</body>
</html>
