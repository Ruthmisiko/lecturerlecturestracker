<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Lecture Tracker') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased" style="background: url('{{ asset('images/image.png') }}') no-repeat center center / cover fixed;">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4" style="background-color: rgba(0, 0, 0, 0.6); position: relative;">
            <a href="https://nawema.co.ke" target="_blank" rel="noopener" title="Visit nawema.co.ke"
               style="position:absolute; top:20px; left:24px; width:64px; height:64px; border-radius:50%; background:#fff; border:3px solid #f26a2e; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 14px rgba(45,42,107,.15);">
                <img src="{{ asset('images/nawema-logo.png') }}" alt="Nawema" style="width:70%; height:70%; object-fit:contain;">
            </a>
            <div>
                <h1 style="color:#fff;font-size:1.75rem;"><b>Lecture Tracker</b></h1>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
