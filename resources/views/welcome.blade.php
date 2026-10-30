<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lecture Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    /* Nawema brand colours */
    :root { --navy: #2d2a6b; --orange: #f26a2e; --orange-dark: #d9561c; }
    body {
        font-family: 'Figtree', sans-serif;
        background: url('{{ asset('images/image.png') }}') no-repeat center center;
        background-size: cover;
    }
    .overlay {
        background-color: rgba(0, 0, 0, 0.6);
    }
    .brand-logo {
        width: 64px; height: 64px; border-radius: 50%;
        background: #fff; border: 3px solid var(--orange);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 14px rgba(45, 42, 107, 0.15);
        transition: transform .15s ease;
    }
    .brand-logo:hover { transform: scale(1.05); }
    .brand-logo img { width: 70%; height: 70%; object-fit: contain; }
    .btn-orange { background: var(--orange); color: #fff; border-radius: 0; }
    .btn-orange:hover { background: var(--orange-dark); }
</style>

</head>
<body class="antialiased text-white">
    <div class="overlay min-h-screen flex flex-col">
        <header class="px-6 sm:px-10 py-5">
            <a href="https://nawema.co.ke" target="_blank" rel="noopener" class="brand-logo" title="Visit nawema.co.ke">
                <img src="{{ asset('images/nawema-logo.png') }}" alt="Nawema">
            </a>
        </header>

        <main class="flex-1 flex flex-col items-center justify-center px-6 pb-16 text-center">
            <h1 class="text-4xl sm:text-5xl font-bold mb-6">Lecture Management System</h1>

            <p class="max-w-2xl text-lg sm:text-xl mb-10 leading-relaxed">
                This system helps accountants and administrators track lecturers’ teaching activities—dates, hours taught,
                and more—for accurate and efficient payment processing.
            </p>

            <div class="space-x-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn-orange inline-block px-8 py-3 font-bold">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-orange inline-block px-8 py-3 font-bold">Login</a>

                        {{-- Register button hidden for now; remove this comment wrapper to show it again.
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="bg-green-500 hover:bg-green-600 px-6 py-3 rounded text-white font-semibold">Register</a>
                        @endif
                        --}}
                    @endauth
                @endif
            </div>
        </main>
    </div>
</body>
</html>
