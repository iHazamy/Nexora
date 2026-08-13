@extends('layouts.app')

@section('title', 'Packages')

@php
    use App\Support\Money;
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Packages</h1>
            <p class="page-subtitle">
                Bundles of products sold as one line.
                {{ $response->total() }} {{ Str::plural('package', $response->total()) }}.
            </p>
        </div>

        @if ($apiSession->canCreate('packages'))
            <a href="{{ route('packages.create') }}" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                New package
            </a>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <x-search-form placeholder="Search packages…">
                <div class="w-36">
                    <label for="active" class="sr-only">Availability</label>
                    <select id="active" name="active" class="input" x-on:change="submitNow()">
                        <option value="">All</option>
                        <option value="1" @selected(request()->query('active') === '1')>Active</option>
                        <option value="0" @selected(request()->query('active') === '0')>Inactive</option>
                    </select>
                </div>
            </x-search-form>
        </div>

        @if ($packages === [])
            @if (request()->hasAny(['search', 'active']))
                <x-empty-state icon="search" title="No packages match those filters">
                    <a href="{{ route('packages.index') }}" class="btn-secondary btn-sm">Clear filters</a>
                </x-empty-state>
            @else
                <x-empty-state
                    icon="packages"
                    title="No packages yet"
                    text="A package bundles products into one invoice line — usually priced below the sum of its parts."
                >
                    @if ($apiSession->canCreate('packages'))
                        <a href="{{ route('packages.create') }}" class="btn-primary btn-sm">
                            <x-icon name="plus" class="h-4 w-4" />
                            New package
                        </a>
                    @endif
                </x-empty-state>
            @endif
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><x-sort-link column="name" label="Package" /></th>
                            <th scope="col" class="numeric text-right">
                                <x-sort-link column="price" label="Price" default="desc" />
                            </th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($packages as $package)
                            <tr>
                                <td>
                                    <a href="{{ route('packages.show', $package['id']) }}"
                                       class="font-medium text-slate-900 hover:text-brand-600">
                                        {{ $package['name'] }}
                                    </a>
                                    @unless ($package['active'])
                                        <span class="badge-neutral ml-1.5">Inactive</span>
                                    @endunless
                                    @if (! empty($package['description']))
                                        <p class="mt-0.5 max-w-md truncate text-xs text-slate-500">
                                            {{ $package['description'] }}
                                        </p>
                                    @endif
                                </td>
                                <td class="numeric text-right font-medium text-slate-900">
                                    {{ Money::amount($package['price']) }}
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($apiSession->canUpdate('packages'))
                                            <a href="{{ route('packages.edit', $package['id']) }}"
                                               class="btn-ghost btn-sm" title="Edit {{ $package['name'] }}">
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                <span class="sr-only">Edit</span>
                                            </a>
                                        @endif

                                        {{-- The remedy sits next to the refusal: the API declines to
                                             delete a package an invoice references. --}}
                                        @if ($apiSession->canUpdate('packages') && $package['active'])
                                            <x-deactivate-form
                                                :action="route('packages.update', $package['id'])"
                                                :confirm="'Deactivate ' . $package['name'] . '? It stays on invoices that already use it, but will not be offered for new ones.'"
                                            />
                                        @endif

                                        @if ($apiSession->canDelete('packages'))
                                            <x-delete-form
                                                :action="route('packages.destroy', $package['id'])"
                                                :confirm="'Delete ' . $package['name'] . '? This cannot be undone.'"
                                                :label="'Delete ' . $package['name']"
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
