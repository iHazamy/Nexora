@extends('layouts.app')

@php
    use App\Support\DisplayDate;
    use App\Support\Money;

    $status      = $invoice['status'];
    $outstanding = $invoice['outstanding'] ?? '0.00';
    $paid        = $invoice['paid_amount'] ?? '0.00';
    $overdue     = DisplayDate::isOverdue($invoice['due_date'] ?? null);

    // ! Settled and cancelled invoices take no further payment — the API refuses both
    // ! with a 409. Offering the form anyway would be an invitation to an error.
    $acceptsPayment = ! in_array($status, ['PAID', 'CANCELLED'], true) && ! Money::isZero($outstanding);
@endphp

@section('title', $invoice['invoice_number'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('invoices.index') }}"
           class="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
            <x-icon name="chevron-left" class="h-3.5 w-3.5" />
            Back to invoices
        </a>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="page-title">{{ $invoice['invoice_number'] }}</h1>
                    <x-status-badge :status="$status" :overdue="$overdue" />
                </div>
                <p class="page-subtitle">
                    {{ $invoice['customer']['name'] ?? '—' }}
                    &middot; issued {{ DisplayDate::date($invoice['invoice_date']) }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('invoices.print', $invoice['id']) }}" target="_blank" class="btn-secondary">
                    <x-icon name="download" class="h-4 w-4" />
                    Print
                </a>

                @if ($apiSession->canUpdate('invoices') && $status !== 'CANCELLED')
                    <a href="{{ route('invoices.edit', $invoice['id']) }}" class="btn-secondary">
                        <x-icon name="pencil" class="h-4 w-4" />
                        Edit
                    </a>
                @endif

                @if ($apiSession->canDelete('invoices') && $status !== 'CANCELLED')
                    {{--
                        ! The confirm text names what will ACTUALLY happen, which differs by
                          state: only an unpaid draft is deleted; anything issued is cancelled
                          and kept, because deleting it would destroy a financial record and
                          leave a hole in the numbering. The API decides and reports which.
                    --}}
                    @php
                        $willDelete = $status === 'DRAFT' && Money::isZero($paid);
                    @endphp
                    <x-delete-form
                        :action="route('invoices.destroy', $invoice['id'])"
                        :label="$willDelete ? 'Delete' : 'Cancel invoice'"
                        :confirm="$willDelete
                            ? 'Delete draft ' . $invoice['invoice_number'] . '? This cannot be undone.'
                            : 'Cancel ' . $invoice['invoice_number'] . '? It stays on record and keeps its number, but can no longer be edited.'"
                        class="btn-danger"
                    />
                @endif
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ Money --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="card"><div class="card-body">
            <p class="metric-label">Invoice total</p>
            <p class="metric-value">{{ Money::format($invoice['total']) }}</p>
        </div></div>

        <div class="card"><div class="card-body">
            <p class="metric-label">Paid</p>
            <p class="metric-value text-emerald-700">{{ Money::format($paid) }}</p>
        </div></div>

        <div class="card"><div class="card-body">
            <p class="metric-label">Outstanding</p>
            {{-- All three figures come from the API. Nothing here subtracts. --}}
            <p @class([
                'metric-value',
                'text-slate-400' => Money::isZero($outstanding),
                'text-rose-700'  => $overdue && ! Money::isZero($outstanding),
            ])>{{ Money::format($outstanding) }}</p>
            @if ($overdue && ! Money::isZero($outstanding))
                <p class="metric-note text-rose-600">
                    Due {{ DisplayDate::date($invoice['due_date']) }} &middot;
                    {{ abs(DisplayDate::daysUntil($invoice['due_date'])) }} days overdue
                </p>
            @elseif (! empty($invoice['due_date']))
                <p class="metric-note">Due {{ DisplayDate::date($invoice['due_date']) }}</p>
            @endif
        </div></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">

            {{-- ------------------------------------------------------------- Items --}}
            <div class="card">
                <div class="card-header"><h2 class="card-title">Items</h2></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Description</th>
                                <th scope="col" class="numeric text-right">Qty</th>
                                <th scope="col" class="numeric text-right">Unit price</th>
                                <th scope="col" class="numeric text-right">Discount</th>
                                <th scope="col" class="numeric text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice['items'] as $item)
                                <tr>
                                    <td class="text-slate-800">
                                        {{ $item['description'] }}
                                        @if (! empty($item['package_id']))
                                            <span class="badge-neutral ml-1">Package</span>
                                        @endif
                                    </td>
                                    <td class="numeric text-right text-slate-600">{{ $item['quantity'] }}</td>
                                    <td class="numeric text-right text-slate-600">
                                        {{ Money::amount($item['unit_price']) }}
                                    </td>
                                    <td class="numeric text-right text-slate-600">
                                        @if (Money::isZero($item['discount'] ?? '0.00'))
                                            <span class="text-slate-300">—</span>
                                        @else
                                            &minus; {{ Money::amount($item['discount']) }}
                                        @endif
                                    </td>
                                    <td class="numeric text-right font-medium text-slate-900">
                                        {{ Money::amount($item['line_total']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-2 border-t border-slate-200 px-5 py-4 sm:px-6">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-600">Subtotal</span>
                        <span class="text-slate-900 tabular">{{ Money::amount($invoice['subtotal']) }}</span>
                    </div>
                    @if (! Money::isZero($invoice['discount']))
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Invoice discount</span>
                            <span class="text-slate-900 tabular">&minus; {{ Money::amount($invoice['discount']) }}</span>
                        </div>
                    @endif
                    @if (! Money::isZero($invoice['tax']))
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Tax</span>
                            <span class="text-slate-900 tabular">{{ Money::amount($invoice['tax']) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold">
                        <span class="text-slate-900">Total</span>
                        <span class="text-slate-900 tabular">{{ Money::amount($invoice['total']) }}</span>
                    </div>
                </div>
            </div>

            {{-- ---------------------------------------------------------- Payments --}}
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Payments</h2>
                        {{-- Stated plainly: there is no gateway anywhere in this system. --}}
                        <p class="card-subtitle">Recorded manually — Runovia does not collect payments.</p>
                    </div>
                </div>

                @if (($invoice['payments'] ?? []) === [])
                    <x-empty-state icon="payments" title="No payments recorded"
                                   text="Record a payment when money arrives." />
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Method</th>
                                    <th scope="col">Reference</th>
                                    <th scope="col" class="numeric text-right">Amount</th>
                                    <th scope="col"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice['payments'] as $payment)
                                    <tr>
                                        <td class="text-slate-700">
                                            {{ DisplayDate::date($payment['payment_date']) }}
                                        </td>
                                        <td class="text-slate-700">
                                            {{ $methods[$payment['method']] ?? $payment['method'] }}
                                        </td>
                                        <td class="text-slate-500">{{ $payment['reference'] ?: '—' }}</td>
                                        <td class="numeric text-right font-medium text-slate-900">
                                            {{ Money::amount($payment['amount']) }}
                                        </td>
                                        <td class="text-right">
                                            @if ($apiSession->canDelete('payments'))
                                                <x-delete-form
                                                    :action="route('payments.destroy', $payment['id'])"
                                                    :confirm="'Reverse this payment of ' . Money::format($payment['amount']) . '? The invoice balance will change.'"
                                                    label="Reverse payment"
                                                    icon-only
                                                />
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- ------------------------------------------------------------- Sidebar --}}
        <div class="space-y-6">

            @if ($acceptsPayment && $apiSession->canCreate('payments'))
                <div class="card">
                    <div class="card-header"><h2 class="card-title">Record a payment</h2></div>
                    <form method="POST" action="{{ route('invoices.payments.store', $invoice['id']) }}">
                        @csrf
                        <div class="card-body space-y-4">
                            {{--
                                ! Pre-filled with the outstanding balance, which is the amount
                                  being paid in the overwhelmingly common case. Overpayment is
                                  refused by the API with a message naming the balance, so the
                                  max attribute here is a convenience and not the check.
                            --}}
                            <x-form.input
                                name="amount"
                                label="Amount received"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="Money::input($outstanding)"
                                :value="Money::input($outstanding)"
                                :prefix="Money::CURRENCY"
                                required
                            />

                            <x-form.input
                                name="payment_date"
                                label="Date received"
                                type="date"
                                :value="now()->format('Y-m-d')"
                                required
                            />

                            <x-form.select
                                name="method"
                                label="Method"
                                :options="$methods"
                                value="BANK_TRANSFER"
                                required
                            />

                            <x-form.input
                                name="reference"
                                label="Reference"
                                hint="Optional — a transaction or cheque number."
                                placeholder="FT2611050001"
                            />
                        </div>

                        <div class="border-t border-slate-200/80 px-5 py-4 sm:px-6">
                            <button type="submit" class="btn-primary w-full">Record payment</button>
                        </div>
                    </form>
                </div>
            @elseif ($status === 'PAID')
                <div class="card"><div class="card-body">
                    <div class="alert-success">
                        <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>This invoice is paid in full.</span>
                    </div>
                </div></div>
            @endif

            <div class="card">
                <div class="card-header"><h2 class="card-title">Details</h2></div>
                <div class="card-body space-y-4 text-sm">
                    <div>
                        <p class="metric-label">Customer</p>
                        <p class="mt-1">
                            <a href="{{ route('customers.show', $invoice['customer_id']) }}"
                               class="font-medium text-brand-600 hover:text-brand-700">
                                {{ $invoice['customer']['name'] ?? '—' }}
                            </a>
                        </p>
                        @if (! empty($invoice['attention']))
                            <p class="text-xs text-slate-500">Attn: {{ $invoice['attention'] }}</p>
                        @endif
                    </div>

                    @if (! empty($invoice['event_date']))
                        <div>
                            <p class="metric-label">Event date</p>
                            <p class="mt-1 text-slate-800">{{ DisplayDate::long($invoice['event_date']) }}</p>
                        </div>
                    @endif

                    @if (! empty($invoice['bank_account']))
                        <div>
                            <p class="metric-label">Pay into</p>
                            <p class="mt-1 text-slate-800">{{ $invoice['bank_account']['bank_name'] }}</p>
                            {{-- A string, always. Never cast an account number. --}}
                            <p class="text-slate-600 tabular">{{ $invoice['bank_account']['account_number'] }}</p>
                            @if (! empty($invoice['bank_account']['account_holder']))
                                <p class="text-xs text-slate-500">{{ $invoice['bank_account']['account_holder'] }}</p>
                            @endif
                        </div>
                    @endif

                    @if (! empty($invoice['notes']))
                        <div>
                            <p class="metric-label">Notes</p>
                            <p class="mt-1 whitespace-pre-line text-slate-700">{{ $invoice['notes'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
