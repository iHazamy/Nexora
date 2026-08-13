{{--
    A sortable column header.

    ! Preserves the whole current query string and only swaps `sort`/`direction`, so
      sorting does not silently discard the search term. It also resets to page 1 —
      re-sorting while on page 3 would otherwise show page 3 of a newly-ordered list,
      which looks like rows going missing.
--}}
@props(['column', 'label', 'default' => 'asc'])

@php
    $activeSort = (string) request()->query('sort', '');
    $activeDirection = request()->query('direction') === 'desc' ? 'desc' : 'asc';
    $isActive = $activeSort === $column;

    // # Clicking the active column flips it; clicking a new one starts at its default.
    $nextDirection = $isActive ? ($activeDirection === 'asc' ? 'desc' : 'asc') : $default;

    $href = request()->fullUrlWithQuery([
        'sort' => $column,
        'direction' => $nextDirection,
        'page' => 1,
    ]);
@endphp

<a href="{{ $href }}" class="group inline-flex items-center gap-1 hover:text-slate-700"
   @if ($isActive) aria-sort="{{ $activeDirection === 'asc' ? 'ascending' : 'descending' }}" @endif>
    {{ $label }}
    <span @class([
        'text-[0.625rem] leading-none',
        'text-brand-600' => $isActive,
        'text-slate-300 group-hover:text-slate-400' => ! $isActive,
    ])>
        @if ($isActive)
            {{ $activeDirection === 'asc' ? '▲' : '▼' }}
        @else
            ▲
        @endif
    </span>
</a>
