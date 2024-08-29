<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
{{-- This layout is for unauthenticated user interface routes. --}}
<head>
    {{-- Metadata --}}
    <meta charset="utf-8"/>
    <meta name="description" content="PausePi">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    {{-- Title --}}
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="font-sans text-white bg-black">
<div id="app" class="container mx-auto">
    @yield('content')
</div>
<script type="module">
    @yield('javascript')
</script>
</body>
</html>
