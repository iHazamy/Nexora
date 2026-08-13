{{--
    The shell for pages a signed-out visitor sees: login, register, and the error
    pages that can be reached without a session.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col items-center justify-center bg-slate-100 px-4 py-10">
    <div class="w-full max-w-md">
        <div class="mb-8 flex flex-col items-center gap-3">
            <x-brand-mark class="h-11 w-11" />
            <div class="text-center">
                <h1 class="text-lg font-bold tracking-tight text-slate-900">{{ config('app.name') }}</h1>
                <p class="mt-1 text-sm text-slate-500">@yield('tagline', 'Invoicing for events and venues')</p>
            </div>
        </div>

        {{-- Flash messages from a redirect: an expired session, a completed sign-out. --}}
        @if (session('status'))
            <div class="mb-5">
                <div class="alert-info">{{ session('status') }}</div>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                @yield('content')
            </div>
        </div>

        @hasSection('footer')
            <div class="mt-6 text-center text-sm text-slate-500">
                @yield('footer')
            </div>
        @endif
    </div>
</body>
</html>
