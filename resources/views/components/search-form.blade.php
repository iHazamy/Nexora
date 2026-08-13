{{--
    The search-and-filter bar for a list screen.

    ! A GET form, so the filter state lives in the URL. That makes a filtered list
      bookmarkable and shareable, survives a refresh, and means the back button behaves —
      none of which is true of filters held in JavaScript.

    ! Carries the current `sort` and `direction` through as hidden inputs. Without them,
      searching would silently reset the user's chosen ordering.
--}}
@props(['placeholder' => 'Search…'])

<form method="GET" action="{{ url()->current() }}" x-data="listFilter"
      class="flex flex-wrap items-end gap-3">

    {{-- Reset to the first page whenever the filters change; listFilter also does this
         for the JS path, and this covers a plain submit with JS unavailable. --}}
    <input type="hidden" name="page" value="1">

    @if (request()->filled('sort'))
        <input type="hidden" name="sort" value="{{ request()->query('sort') }}">
    @endif
    @if (request()->filled('direction'))
        <input type="hidden" name="direction" value="{{ request()->query('direction') }}">
    @endif

    <div class="min-w-[12rem] flex-1">
        <label for="search" class="sr-only">Search</label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <x-icon name="search" class="h-4 w-4" />
            </span>
            <input type="search" id="search" name="search" value="{{ request()->query('search') }}"
                   placeholder="{{ $placeholder }}" class="input pl-9"
                   @input="submitDebounced()">
        </div>
    </div>

    {{ $slot }}

    <button type="submit" class="btn-secondary">Apply</button>

    @if (request()->hasAny(['search', 'status', 'type', 'active', 'method', 'action', 'entity_type']))
        <a href="{{ url()->current() }}" class="btn-ghost">Clear</a>
    @endif
</form>
