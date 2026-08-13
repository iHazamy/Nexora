@extends('layouts.platform')

@section('title', 'Businesses')

@php
    use App\Support\DisplayDate;

    $badgeFor = static fn (string $status): string => match ($status) {
        'ACTIVE'      => 'badge-paid',
        'SUSPENDED'   => 'badge-cancelled',
        'MAINTENANCE' => 'badge-partial',
        'CLOSED'      => 'badge-neutral',
        default       => 'badge-neutral',
    };
@endphp

@section('content')
    <div class="mb-6">
        <h1 class="page-title">Businesses</h1>
        <p class="page-subtitle">
            {{ $response->total() }} {{ Str::plural('business', $response->total()) }} on the platform.
        </p>
    </div>

    {{--
        ! Stated on the screen, not just in a code comment. There is deliberately no route
          anywhere in this API that reads a tenant's customers, invoices or payments from the
          platform surface, and no UI here should imply one is coming. Support access to
          tenant DATA is a separate decision with its own PDPA consequences; it must not
          arrive as a side effect of an admin role that already exists.
    --}}
    <div class="mb-5 alert-info">
        <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0" />
        <div>
            <p class="font-medium">This surface controls access, not data.</p>
            <p class="mt-0.5">
                Tenant customers, invoices and payments are not readable from here by design.
                Changing a status takes effect on that business's very next request.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ url()->current() }}" x-data="listFilter"
                  class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="page" value="1">

                <div class="min-w-[12rem] flex-1">
                    <label for="search" class="sr-only">Search</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <x-icon name="search" class="h-4 w-4" />
                        </span>
                        <input type="search" id="search" name="search" class="input pl-9"
                               value="{{ request()->query('search') }}"
                               placeholder="Search business name…" x-on:input="submitDebounced()">
                    </div>
                </div>

                <div class="w-44">
                    <label for="status" class="sr-only">Status</label>
                    <select id="status" name="status" class="input" x-on:change="submitNow()">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(request()->query('status') === $status)>
                                {{ Str::headline(Str::lower($status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-secondary">Apply</button>

                @if (request()->hasAny(['search', 'status']))
                    <a href="{{ url()->current() }}" class="btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        @if ($businesses === [])
            <x-empty-state icon="building" title="No businesses match" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Business</th>
                            <th scope="col">Contact</th>
                            <th scope="col">Registered</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Change status</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($businesses as $business)
                            @php $status = (string) ($business['status'] ?? 'ACTIVE'); @endphp
                            <tr>
                                <td>
                                    <p class="font-medium text-slate-900">{{ $business['name'] }}</p>
                                    <p class="text-xs text-slate-500">#{{ $business['id'] }}</p>
                                </td>
                                <td class="text-slate-600">
                                    @if (! empty($business['email']))
                                        <p>{{ $business['email'] }}</p>
                                    @endif
                                    @if (! empty($business['phone']))
                                        <p class="text-xs text-slate-500">{{ $business['phone'] }}</p>
                                    @endif
                                    @if (empty($business['email']) && empty($business['phone']))
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="text-slate-500">
                                    {{ DisplayDate::instant($business['created_at'] ?? null, 'j M Y') }}
                                </td>
                                <td>
                                    <span class="{{ $badgeFor($status) }}">
                                        {{ Str::headline(Str::lower($status)) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    {{--
                                        ! A real POST form with method spoofing, never a GET link — a
                                          link that suspends a company would be followed by prefetch.

                                        ! The confirmation names the business AND the consequence,
                                          because business status is auth gate 10 and is read live:
                                          every one of that tenant's signed-in users is stopped on
                                          their next request, mid-session and without warning.
                                    --}}
                                    <form method="POST"
                                          action="{{ route('platform.businesses.status', $business['id']) }}"
                                          class="inline-flex items-center gap-2"
                                          x-data="{ next: @js($status) }"
                                          x-on:submit="
                                              if (next === @js($status)) { $event.preventDefault(); return; }
                                              if (! confirm(
                                                  next === 'ACTIVE'
                                                      ? 'Reactivate {{ addslashes($business['name']) }}? Its users will be able to sign in again.'
                                                      : 'Set {{ addslashes($business['name']) }} to ' + next + '?\n\nEvery signed-in user of this business is blocked from their next request onward — immediately, not at next login.'
                                              )) { $event.preventDefault(); }">
                                        @csrf
                                        @method('PUT')

                                        <label for="status-{{ $business['id'] }}" class="sr-only">
                                            Status for {{ $business['name'] }}
                                        </label>
                                        <select id="status-{{ $business['id'] }}" name="status"
                                                class="input input-sm w-36" x-model="next">
                                            @foreach ($statuses as $option)
                                                <option value="{{ $option }}" @selected($status === $option)>
                                                    {{ Str::headline(Str::lower($option)) }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <button type="submit" class="btn-secondary btn-sm"
                                                x-bind:disabled="next === @js($status)">
                                            Apply
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-pagination :response="$response" />
        @endif
    </div>
@endsection
