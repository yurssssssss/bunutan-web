<!doctype html>
<html lang="tl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Bunutan')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap">
    {{-- ?v= changes whenever the file does, so phones don't keep an old copy after a deploy --}}
    <link rel="stylesheet" href="{{ asset('css/roulette.css') }}?v={{ filemtime(public_path('css/roulette.css')) }}">
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
