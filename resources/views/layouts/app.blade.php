<!doctype html>
<html lang="tl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Bunutan')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap">
    {{-- ?v= is a fingerprint of the file's contents, so phones load the new copy after every change --}}
    <link rel="stylesheet" href="{{ asset('css/roulette.css') }}?v={{ substr(md5_file(public_path('css/roulette.css')), 0, 10) }}">
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
