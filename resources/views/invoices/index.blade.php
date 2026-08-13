@extends('layouts.app')

@section('title', 'Invoices')

@php
    use App\Support\DisplayDate;
    use App\Support\Money;
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Invoices</h1>
            <p class="page-subtitle">{{ $response->total() }} {{ Str::plural('invoice', $response->total()) }}</p>
        </div>

        @if ($apiSession->canCreate('invoices'))
            <a href="{{ route('invoices.create') }}" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                New invoice
            </a>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <x-search-form placeholder="Search invoice number or notes…">
                <div class="w-40">
                    <label for="status" class="sr-only">Status</label>
                    <select id="status" name="status" class="input" @change="submitNow()">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(request()->query('status') === $status)>
                                {{ Str::headline(Str::lower($status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-48">
                    <label for="customer_id" class="sr-only">Customer</label>
                    <select id="customer_id" name="customer_id" class="input" @change="submitNow()">
                        <option value="">All customers</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer['id'] }}"
                                @selected((string) request()->query('customer_id') === (string) $customer['id'])>
                                {{ $customer['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </x-search-form>
        </div>

        @if ($invoices === [])
            @if (request()->hasAny(['search', 'status', 'customer_id']))
                <x-empty-state icon="search" title="No invoices match those filters"
                               text="Try widening the search or clearing the filters.">
                    <a href="{{ route('invoices.index') }}" class="btn-secondary btn-sm">Clear filters</a>
                </x-empty-state>
            @else
                <x-empty-state icon="invoice" title="No invoices yet"
                               text="Raise your first invoice to start billing. You will need a customer first.">
                    @if ($apiSession->canCreate('invoices'))
                        <a href="{{ route('invoices.create') }}" class="btn-primary btn-sm">
                            <x-icon name="plus" class="h-4 w-4" />
                            New invoice
                        </a>
                    @endif
                </x-empty-state>
            @endif
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><x-sort-link column="invoice_number" label="Invoice" /></th>
                            <th scope="col">Customer</th>
                            <th scope="col"><x-sort-link column="invoice_date" label="Date" default="desc" /></th>
                            <th scope="col" class="hidden lg:table-cell">
                                <x-sort-link column="due_date" label="Due" default="desc" />
                            </th>
                            <th scope="col"><x-sort-link column="status" label="Status" /></th>
                            <th scope="col" class="numeric text-right">
                                <x-sort-link column="total" label="Total" default="desc" />
                            </th>
                            <th scope="col" class="numeric text-right">Outstanding</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            @php
                                // ! `outstanding` and `paid_amount` come from the API. Both are
                                // ! fixed-2 strings; subtracting them here would reintroduce the
                                // ! float error the string encoding exists to prevent.
                                $outstanding = $invoice['outstanding'] ?? '0.00';
                                $overdue = DisplayDate::isOverdue($invoice['due_date'] ?? null);
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('invoices.show', $invoice['id']) }}"
                                       class="font-medium text-slate-900 hover:text-brand-600">
                                        {{ $invoice['invoice_number'] }}
                                    </a>
                                    @if (! empty($invoice['event_date']))
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            Event {{ DisplayDate::date($invoice['event_date']) }}
                                        </p>
                                    @endif
                                </td>
                                <td class="text-slate-700">{{ $invoice['customer_name'] ?? '—' }}</td>
                                <td class="text-slate-500">{{ DisplayDate::date($invoice['invoice_date']) }}</td>
                                <td class="hidden lg:table-cell">
                                    <span @class(['text-rose-600 font-medium' => $overdue, 'text-slate-500' => ! $overdue])>
                                        {{ DisplayDate::date($invoice['due_date'] ?? null) }}
                                    </span>
                                </td>
                                <td><x-status-badge :status="$invoice['status']" :overdue="$overdue" /></td>
                                <td class="numeric text-right text-slate-700">
                                    {{ Money::amount($invoice['total']) }}
                                </td>
                                <td class="numeric text-right">
                                    <span @class([
                                        'font-medium',
                                        'text-slate-400' => Money::isZero($outstanding),
                                        'text-slate-900' => ! Money::isZero($outstanding),
                                    ])>
                                        {{ Money::amount($outstanding) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('invoices.print', $invoice['id']) }}" target="_blank"
                                       class="btn-ghost btn-sm" title="Print {{ $invoice['invoice_number'] }}">
                                        <x-icon name="download" class="h-3.5 w-3.5" />
                                        <span class="sr-only">Print</span>
                                    </a>
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
