@extends('layouts.app')

@section('title', 'Products & services')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Products &amp; services</h1>
            <p class="page-subtitle">
                {{ $response->total() }} {{ Str::plural('item', $response->total()) }} in your catalogue
            </p>
        </div>

        {{-- Hidden when the API would refuse it. A hint, not the control — see ApiSession. --}}
        @if ($apiSession->canCreate('products'))
            <a href="{{ route('products.create') }}" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                New item
            </a>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            {{--
                ! The filters live in the query string, so a filtered list is
                  bookmarkable and the back button behaves. x-on:change submits through
                  the listFilter component that x-search-form already declares.
            --}}
            <x-search-form placeholder="Search name or description…">
                <div class="w-full sm:w-40">
                    <x-form.select
                        name="type"
                        label="Type"
                        placeholder="Any type"
                        :value="request()->query('type')"
                        :options="['PRODUCT' => 'Products', 'SERVICE' => 'Services']"
                        x-on:change="submitNow()"
                    />
                </div>

                <div class="w-full sm:w-40">
                    <x-form.select
                        name="active"
                        label="Status"
                        placeholder="Any status"
                        :value="request()->query('active')"
                        :options="['1' => 'Active only', '0' => 'Inactive only']"
                        x-on:change="submitNow()"
                    />
                </div>
            </x-search-form>
        </div>

        @if ($products === [])
            @if (request()->hasAny(['search', 'type', 'active']))
                <x-empty-state
                    icon="search"
                    title="Nothing matches those filters"
                    text="Try a different search term, or widen the type and status filters."
                >
                    <a href="{{ route('products.index') }}" class="btn-secondary btn-sm">Clear filters</a>
                </x-empty-state>
            @else
                {{-- An empty list is a success, not an error. --}}
                <x-empty-state
                    icon="products"
                    title="Nothing in your catalogue yet"
                    text="Products and services are the priced items you put on an invoice or bundle into a package."
                >
                    @if ($apiSession->canCreate('products'))
                        <a href="{{ route('products.create') }}" class="btn-primary btn-sm">
                            <x-icon name="plus" class="h-4 w-4" />
                            New item
                        </a>
                    @endif
                </x-empty-state>
            @endif
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><x-sort-link column="name" label="Name" /></th>
                            <th scope="col"><x-sort-link column="type" label="Type" /></th>
                            <th scope="col" class="numeric text-right">
                                <x-sort-link column="price" label="Price" />
                            </th>
                            <th scope="col" class="hidden md:table-cell">
                                <x-sort-link column="updated_at" label="Updated" default="desc" />
                            </th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            @php
                                $isActive = (bool) ($product['active'] ?? false);
                                $isService = ($product['type'] ?? '') === 'SERVICE';
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($apiSession->canUpdate('products'))
                                            {{-- The edit form is the detail view: there is no show route. --}}
                                            <a href="{{ route('products.edit', $product['id']) }}"
                                               class="font-medium text-slate-900 hover:text-brand-600">
                                                {{ $product['name'] }}
                                            </a>
                                        @else
                                            <span class="font-medium text-slate-900">{{ $product['name'] }}</span>
                                        @endif

                                        {{--
                                            ! Deactivated rows are LISTED, not hidden. They stay readable
                                              because issued invoices still reference them; the badge is how
                                              the user tells the two apart.
                                        --}}
                                        @if (! $isActive)
                                            <span class="badge-neutral">Inactive</span>
                                        @endif
                                    </div>

                                    @if (! empty($product['description']))
                                        <p class="mt-0.5 max-w-md truncate text-xs text-slate-500">
                                            {{ $product['description'] }}
                                        </p>
                                    @endif
                                </td>
                                <td>
                                    <span class="{{ $isService ? 'badge-sent' : 'badge-draft' }}">
                                        {{ $isService ? 'Service' : 'Product' }}
                                    </span>
                                </td>
                                {{-- Right-aligned with tabular figures so the column reads as a column. --}}
                                <td class="numeric text-right font-medium text-slate-900">
                                    {{ \App\Support\Money::amount($product['price']) }}
                                </td>
                                <td class="hidden text-slate-500 md:table-cell">
                                    {{ \App\Support\DisplayDate::instant($product['updated_at'] ?? null, 'j M Y') }}
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($apiSession->canUpdate('products'))
                                            <a href="{{ route('products.edit', $product['id']) }}"
                                               class="btn-ghost btn-sm" title="Edit {{ $product['name'] }}">
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                <span class="sr-only">Edit</span>
                                            </a>

                                            {{-- The remedy for a refused delete, next to the delete. --}}
                                            @if ($isActive)
                                                @include('products.partials.deactivate', [
                                                    'action' => route('products.update', $product['id']),
                                                    'confirm' => 'Deactivate ' . $product['name']
                                                        . '? It stays on invoices that already use it, but will not be'
                                                        . ' offered on new ones.',
                                                ])
                                            @endif
                                        @endif

                                        @if ($apiSession->canDelete('products'))
                                            <x-delete-form
                                                :action="route('products.destroy', $product['id'])"
                                                :confirm="'Delete ' . $product['name'] . '? This cannot be undone.'"
                                                :label="'Delete ' . $product['name']"
                                                icon-only
                                            />
                                        @endif
                                    </div>
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
