{{--
    The signed-in application shell.

    ! Every permission-driven decision in here — which nav items show, which buttons
      show — comes from $apiSession->canWrite(), which is a DISPLAY HINT MIRRORING the
      API's role matrix. It is not enforcement. The API re-checks every request at its
      gate 11 and is the only thing that decides. Hiding a button the API would refuse
      is a courtesy; relying on the hidden button as the control would be a bug.

    ? $apiSession is bound to every view by RunoviaServiceProvider, so no controller
      has to pass it.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full">
<div x-data="{ mobileNav: false }" class="lg:flex lg:min-h-screen">

    {{-- ------------------------------------------------------------- Sidebar --}}
    <aside class="hidden w-64 shrink-0 flex-col bg-slate-950 lg:flex">
        @include('layouts.partials.sidebar')
    </aside>

    {{-- Mobile drawer. x-cloak stops it flashing open before Alpine boots. --}}
    <div x-show="mobileNav" x-cloak class="relative z-40 lg:hidden" role="dialog" aria-modal="true">
        <div x-show="mobileNav" x-transition.opacity class="fixed inset-0 bg-slate-900/70"
             @click="mobileNav = false" aria-hidden="true"></div>
        <aside x-show="mobileNav"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
               class="fixed inset-y-0 left-0 flex w-64 flex-col bg-slate-950">
            @include('layouts.partials.sidebar')
        </aside>
    </div>

    {{-- ---------------------------------------------------------------- Main --}}
    <div class="flex min-w-0 flex-1 flex-col">

        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="flex items-center gap-3 px-4 py-3 sm:px-6">
                <button type="button" @click="mobileNav = true"
                        class="btn-ghost lg:hidden" aria-label="Open navigation">
                    <x-icon name="menu" />
                </button>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-900">
                        {{ $apiSession->businessName() }}
                    </p>
                    <p class="truncate text-xs text-slate-500">
                        {{ $apiSession->userName() }} &middot; {{ __('roles.' . ($apiSession->role() ?? 'MB')) }}
                    </p>
                </div>

                @yield('header-actions')

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-ghost" title="Sign out">
                        <x-icon name="logout" class="h-5 w-5" />
                        <span class="sr-only">Sign out</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">

                {{--
                    ! Flash and error messages render HERE, once, above every page.
                      `runovia` is the key the ApiException handler uses for a refusal
                      it bounced back to the form, so an API 400 or 409 surfaces
                      without each individual view remembering to display it.
                --}}
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

                @yield('content')
            </div>
        </main>
    </div>
</div>
</body>
</html>
