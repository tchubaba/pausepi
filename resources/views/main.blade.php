<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
{{-- This layout is for unauthenticated user interface routes. --}}
<head>
    {{-- Metadata --}}
    <meta charset="utf-8"/>
    <meta name="description" content="PausePi – pause Pi-hole ad blocking from one page">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="theme-color" content="#0f172a">

    {{-- PWA Icons --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/pausepi.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    {{-- Title --}}
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-white">
<div id="app">
    @yield('content')
</div>
@stack('scripts')
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js');
        });
    }
</script>
</body>
</html>
