@extends('layouts.app')

@section('title', $customer['name'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('customers.index') }}"
           class="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
            <x-icon name="chevron-left" class="h-3.5 w-3.5" />
            Back to customers
        </a>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="page-title">{{ $customer['name'] }}</h1>
                <p class="page-subtitle">
                    Added {{ \App\Support\DisplayDate::instant($customer['created_at'], 'j M Y') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($apiSession->canCreate('invoices'))
                    <a href="{{ route('invoices.create', ['customer_id' => $customer['id']]) }}" class="btn-primary">
                        <x-icon name="plus" class="h-4 w-4" />
                        New invoice
                    </a>
                @endif

                @if ($apiSession->canUpdate('customers'))
                    <a href="{{ route('customers.edit', $customer['id']) }}" class="btn-secondary">
                        <x-icon name="pencil" class="h-4 w-4" />
                        Edit
                    </a>
                @endif

                @if ($apiSession->canDelete('customers'))
                    <x-delete-form
                        :action="route('customers.destroy', $customer['id'])"
                        :confirm="'Delete ' . $customer['name'] . '? This cannot be undone.'"
                        class="btn-danger"
                    />
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- ------------------------------------------------------------ Details --}}
        <div class="space-y-6 lg:col-span-1">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Contact</h2></div>
                <div class="card-body space-y-4 text-sm">
                    <div>
                        <p class="metric-label">Email</p>
                        <p class="mt-1 text-slate-800">
                            @if (! empty($customer['email']))
                                <a href="mailto:{{ $customer['email'] }}"
                                   class="text-brand-600 hover:text-brand-700">{{ $customer['email'] }}</a>
                            @else
                                <span class="text-slate-400">Not provided</span>
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="metric-label">Phone</p>
                        <p class="mt-1 text-slate-800">
                            {{ $customer['phone'] ?: '' }}
                            @if (empty($customer['phone']))
                                <span class="text-slate-400">Not provided</span>
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="metric-label">Address</p>
                        <p class="mt-1 whitespace-pre-line text-slate-800">
                            {{ $customer['address'] ?: '' }}
                            @if (empty($customer['address']))
                                <span class="text-slate-400">Not provided</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            @if (! empty($customer['notes']))
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Notes</h2>
                            {{-- Stated on screen because the field sits next to details that DO print. --}}
                            <p class="card-subtitle">Internal only — never shown on an invoice.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="whitespace-pre-line text-sm text-slate-700">{{ $customer['notes'] }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- ----------------------------------------------------------- Invoices --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Invoices</h2>
                        <p class="card-subtitle">
                            {{ $invoiceTotal }} {{ Str::plural('invoice', $invoiceTotal) }} for this customer
                        </p>
                    </div>

                    @if ($invoiceTotal > count($invoices))
                        <a href="{{ route('invoices.index', ['customer_id' => $customer['id']]) }}"
                           class="btn-secondary btn-sm">View all</a>
                    @endif
                </div>

                @if ($invoices === [])
                    <x-empty-state
                        icon="invoice"
                        title="No invoices yet"
                        text="Raise an invoice to start billing this customer."
                    >
                        @if ($apiSession->canCreate('invoices'))
                            <a href="{{ route('invoices.create', ['customer_id' => $customer['id']]) }}"
                               class="btn-primary btn-sm">
                                <x-icon name="plus" class="h-4 w-4" />
                                New invoice
                            </a>
                        @endif
                    </x-empty-state>
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Invoice</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="numeric text-right">Total</th>
                                    <th scope="col" class="numeric text-right">Outstanding</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoices as $invoice)
                                    @php
                                        // ! Outstanding is computed by the API, never here. Both are
                                        // ! fixed-2 strings and subtracting them in PHP would reintroduce
                                        // ! exactly the float error the string encoding prevents.
                                        $outstanding = $invoice['outstanding'] ?? '0.00';
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('invoices.show', $invoice['id']) }}"
                                               class="font-medium text-slate-900 hover:text-brand-600">
                                                {{ $invoice['invoice_number'] }}
                                            </a>
                                        </td>
                                        <td class="text-slate-500">
                                            {{ \App\Support\DisplayDate::date($invoice['invoice_date']) }}
                                        </td>
                                        <td>
                                            <x-status-badge
                                                :status="$invoice['status']"
                                                :overdue="\App\Support\DisplayDate::isOverdue($invoice['due_date'] ?? null)"
                                            />
                                        </td>
                                        <td class="numeric text-right text-slate-700">
                                            {{ \App\Support\Money::amount($invoice['total']) }}
                                        </td>
                                        <td class="numeric text-right">
                                            <span @class([
                                                'font-medium',
                                                'text-slate-400' => \App\Support\Money::isZero($outstanding),
                                                'text-slate-900' => ! \App\Support\Money::isZero($outstanding),
                                            ])>
                                                {{ \App\Support\Money::amount($outstanding) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
