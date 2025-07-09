<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lecture Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    body {
        font-family: 'Figtree', sans-serif;
        background: url('{{ asset('images/image.png') }}') no-repeat center center;
        background-size: cover;
    }
    .overlay {
        background-color: rgba(0, 0, 0, 0.6);
    }
</style>

</head>
<body class="antialiased text-white">
    <div class="overlay min-h-screen flex flex-col items-center justify-center px-6 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold mb-6">Lecture Management System</h1>

        <p class="max-w-2xl text-lg sm:text-xl mb-10 leading-relaxed">
            This system helps accountants and administrators track lecturers’ teaching activities—dates, hours taught,
            and more—for accurate and efficient payment processing.
        </p>

        <div class="space-x-4">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="bg-green-500 hover:bg-green-600 px-6 py-3 rounded text-white font-semibold">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="bg-blue-500 hover:bg-blue-600 px-6 py-3 rounded text-white font-semibold">Login</a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="bg-green-500 hover:bg-green-600 px-6 py-3 rounded text-white font-semibold">Register</a>
                    @endif
                @endauth
            @endif
        </div>
    </div>
</body>
</html>
