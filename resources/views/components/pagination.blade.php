{{--
    Pagination driven by the API's own `pagination` block.

    ! NOT Laravel's paginator. Laravel's expects to have counted the rows itself; here
      the API did the counting and sent page / per_page / total / total_pages, so this
      renders from those four numbers. Wrapping them in a LengthAwarePaginator just to
      get its links() view back would mean constructing a fake collection.

    ! Every link preserves the current query string, so paging does not silently drop
      the search term or the status filter the user set. That is the bug this component
      exists to not have.
--}}
@props(['response'])

@php
    $page = $response->page();
    $totalPages = $response->totalPages();
    $total = $response->total();
    $perPage = $response->perPage();

    $from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
    $to = min($page * $perPage, $total);

    $link = static fn (int $target): string => request()->fullUrlWithQuery(['page' => $target]);
@endphp

@if ($total > 0)
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 sm:px-6">
        <p class="text-xs text-slate-500 tabular">
            Showing <span class="font-medium text-slate-700">{{ $from }}</span>–<span
                class="font-medium text-slate-700">{{ $to }}</span>
            of <span class="font-medium text-slate-700">{{ $total }}</span>
        </p>

        @if ($totalPages > 1)
            <nav class="flex items-center gap-1" aria-label="Pagination">
                @if ($page > 1)
                    <a href="{{ $link($page - 1) }}" class="btn-secondary btn-sm" rel="prev">
                        <x-icon name="chevron-left" class="h-3.5 w-3.5" />
                        Previous
                    </a>
                @else
                    <span class="btn-secondary btn-sm opacity-40" aria-disabled="true">
                        <x-icon name="chevron-left" class="h-3.5 w-3.5" />
                        Previous
                    </span>
                @endif

                <span class="px-3 text-xs text-slate-500 tabular">
                    Page {{ $page }} of {{ $totalPages }}
                </span>

                @if ($page < $totalPages)
                    <a href="{{ $link($page + 1) }}" class="btn-secondary btn-sm" rel="next">
                        Next
                        <x-icon name="chevron-right" class="h-3.5 w-3.5" />
                    </a>
                @else
                    <span class="btn-secondary btn-sm opacity-40" aria-disabled="true">
                        Next
                        <x-icon name="chevron-right" class="h-3.5 w-3.5" />
                    </span>
                @endif
            </nav>
        @endif
    </div>
@endif
