{{--
    The shared frame for every error screen.

    ! Uses no layout and needs no session. These pages are reached precisely when
      something is wrong — the API is unreachable, the tenant is suspended, the
      credentials are unset — and a frame that itself calls the API or reads the session
      would fail while trying to explain a failure.

    ! `detail` is rendered only when the caller passes it, and every caller gates that on
      config('app.debug'). Internal messages name hosts, config keys and class paths.
--}}
@props([
    'title',
    'message',
    'icon' => 'warning',
    'tone' => 'slate',
    'detail' => null,
])

@php
    $ring = match ($tone) {
        'rose'    => 'bg-rose-50 text-rose-600',
        'amber'   => 'bg-amber-50 text-amber-600',
        'brand'   => 'bg-brand-50 text-brand-600',
        default   => 'bg-slate-100 text-slate-500',
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    @vite('resources/css/app.css')
</head>
<body class="flex min-h-full items-center justify-center bg-slate-100 px-4 py-10">
    <div class="w-full max-w-lg">
        <div class="card">
            <div class="card-body text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full {{ $ring }}">
                    <x-icon :name="$icon" class="h-6 w-6" />
                </div>

                <h1 class="mt-5 text-lg font-bold tracking-tight text-slate-900">{{ $title }}</h1>
                <p class="mt-2 text-sm text-slate-600">{{ $message }}</p>

                @if (! $slot->isEmpty())
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">{{ $slot }}</div>
                @endif

                @if ($detail)
                    <details class="mt-6 text-left">
                        <summary class="cursor-pointer text-xs font-medium text-slate-500 hover:text-slate-700">
                            Technical detail (shown because APP_DEBUG is on)
                        </summary>
                        <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-900 p-3 text-xs
                                    leading-relaxed text-slate-100">{{ $detail }}</pre>
                    </details>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
