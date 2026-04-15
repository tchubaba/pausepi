<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
{{-- This layout is for unauthenticated user interface routes. --}}
<head>
    {{-- Metadata --}}
    <meta charset="utf-8"/>
    <meta name="description" content="PausePi – pause Pi-hole ad blocking from one page">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    {{-- Title --}}
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-white">
<div id="app">
    @yield('content')
</div>
@stack('scripts')
</body>
</html>
