{{--
    The platform-administration shell.

    ! A SEPARATE LAYOUT FROM layouts/app.blade.php, and it has to be. A platform admin (SA)
      reaches NONE of the business modules — Module::forRole() gives SA and the business roles
      disjoint module sets — so the app sidebar's links to customers, invoices, packages and
      settings would every one of them be refused by the API. Rendering that shell here would
      surround the one thing an admin can do with eight things they cannot.

    ! Visually deliberately distinct — a dark header rather than the tenant's indigo rail — so
      an operator can never be in any doubt about which surface they are looking at. Confusing
      "our admin console" with "a customer's account" is exactly the mistake that ends with
      someone suspending the wrong business.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Platform') — {{ config('app.name') }} admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-100">

    <header class="bg-slate-950">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <x-brand-mark class="h-8 w-8" />
            <div class="min-w-0 flex-1">
                <p class="flex items-center gap-2 text-sm font-semibold text-white">
                    {{ config('app.name') }}
                    <span class="rounded bg-amber-400/20 px-1.5 py-0.5 text-[0.6875rem] font-bold uppercase
                                 tracking-wider text-amber-300">Platform</span>
                </p>
                <p class="truncate text-xs text-slate-400">
                    {{ $apiSession->userName() }} &middot; {{ __('roles.SA') }}
                </p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium
                               text-slate-300 transition hover:bg-white/10 hover:text-white">
                    <x-icon name="logout" class="h-4 w-4" />
                    Sign out
                </button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-5 alert-success">
                <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('status'))
            <div class="mb-5 alert-info">{{ session('status') }}</div>
        @endif

        @if ($errors->has('runovia'))
            <div class="mb-5 alert-error">
                <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ $errors->first('runovia') }}</span>
            </div>
        @endif

        @if ($errors->has('status'))
            <div class="mb-5 alert-error">
                <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ $errors->first('status') }}</span>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
