@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Customers</h1>
            <p class="page-subtitle">{{ $response->total() }} {{ Str::plural('customer', $response->total()) }}</p>
        </div>

        {{-- Hidden when the API would refuse it. A hint, not the control — see ApiSession. --}}
        @if ($apiSession->canCreate('customers'))
            <a href="{{ route('customers.create') }}" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                New customer
            </a>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <x-search-form placeholder="Search name, email or phone…" />
        </div>

        @if ($customers === [])
            @if (request()->filled('search'))
                <x-empty-state
                    icon="search"
                    title="No customers match that search"
                    text="Try a different name, email address or phone number."
                >
                    <a href="{{ route('customers.index') }}" class="btn-secondary btn-sm">Clear search</a>
                </x-empty-state>
            @else
                {{-- An empty list is a success, not an error. --}}
                <x-empty-state
                    icon="customers"
                    title="No customers yet"
                    text="Customers are who your invoices are addressed to. Add the first one to get started."
                >
                    @if ($apiSession->canCreate('customers'))
                        <a href="{{ route('customers.create') }}" class="btn-primary btn-sm">
                            <x-icon name="plus" class="h-4 w-4" />
                            New customer
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
                            <th scope="col">Contact</th>
                            <th scope="col" class="hidden md:table-cell">
                                <x-sort-link column="created_at" label="Added" default="desc" />
                            </th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <td>
                                    <a href="{{ route('customers.show', $customer['id']) }}"
                                       class="font-medium text-slate-900 hover:text-brand-600">
                                        {{ $customer['name'] }}
                                    </a>
                                    @if (! empty($customer['address']))
                                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ $customer['address'] }}</p>
                                    @endif
                                </td>
                                <td>
                                    @if (! empty($customer['email']))
                                        <p class="text-slate-700">{{ $customer['email'] }}</p>
                                    @endif
                                    @if (! empty($customer['phone']))
                                        <p class="text-xs text-slate-500">{{ $customer['phone'] }}</p>
                                    @endif
                                    @if (empty($customer['email']) && empty($customer['phone']))
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="hidden text-slate-500 md:table-cell">
                                    {{ \App\Support\DisplayDate::instant($customer['created_at'], 'j M Y') }}
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($apiSession->canUpdate('customers'))
                                            <a href="{{ route('customers.edit', $customer['id']) }}"
                                               class="btn-ghost btn-sm" title="Edit {{ $customer['name'] }}">
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                <span class="sr-only">Edit</span>
                                            </a>
                                        @endif

                                        @if ($apiSession->canDelete('customers'))
                                            <x-delete-form
                                                :action="route('customers.destroy', $customer['id'])"
                                                :confirm="'Delete ' . $customer['name'] . '? This cannot be undone.'"
                                                :label="'Delete ' . $customer['name']"
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
