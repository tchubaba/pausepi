<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
{{-- This layout is for unauthenticated user interface routes. --}}
<head>
    {{-- Metadata --}}
    <meta charset="utf-8"/>
    <meta name="description" content="Pi-hole Pauser">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    {{-- Title --}}
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: black;
            color: white;
        }

        .status {
            font-weight: bold;
            color: green;
        }
    </style>
</head>
<body>
<script type="module">
    @yield('javascript')
</script>
<div id="app">
    @yield('content')
</div>
</body>
</html>
