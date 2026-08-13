@extends('layouts.app')

@section('title', 'Payments')

@php
    use App\Support\DisplayDate;
    use App\Support\Money;
@endphp

@section('content')
    <div class="mb-6">
        <h1 class="page-title">Payments</h1>
        {{-- Said plainly, once, at the top: nothing here collects money. --}}
        <p class="page-subtitle">
            {{ $response->total() }} {{ Str::plural('payment', $response->total()) }} recorded.
            Runovia does not collect payments — these are your own records of money received.
        </p>
    </div>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ url()->current() }}" x-data="listFilter"
                  class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="page" value="1">

                <div class="w-48">
                    <label for="method" class="sr-only">Method</label>
                    <select id="method" name="method" class="input" x-on:change="submitNow()">
                        <option value="">All methods</option>
                        @foreach ($methods as $code => $label)
                            <option value="{{ $code }}" @selected(request()->query('method') === $code)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-secondary">Apply</button>

                @if (request()->hasAny(['method', 'invoice_id']))
                    <a href="{{ url()->current() }}" class="btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        @if ($payments === [])
            @if (request()->hasAny(['method', 'invoice_id']))
                <x-empty-state icon="search" title="No payments match those filters">
                    <a href="{{ route('payments.index') }}" class="btn-secondary btn-sm">Clear filters</a>
                </x-empty-state>
            @else
                <x-empty-state
                    icon="payments"
                    title="No payments recorded yet"
                    text="Open an invoice and record a payment when money arrives."
                >
                    <a href="{{ route('invoices.index') }}" class="btn-secondary btn-sm">Go to invoices</a>
                </x-empty-state>
            @endif
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Invoice</th>
                            <th scope="col">Method</th>
                            <th scope="col">Reference</th>
                            <th scope="col" class="numeric text-right">Amount</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="text-slate-700">
                                    {{ DisplayDate::date($payment['payment_date']) }}
                                </td>
                                <td>
                                    {{--
                                        ! Links by invoice_id, which the payment carries. The
                                          invoice NUMBER is not on a payment row, so the link
                                          text is the id — going and fetching each invoice to
                                          show its number would be one call per row.
                                    --}}
                                    <a href="{{ route('invoices.show', $payment['invoice_id']) }}"
                                       class="font-medium text-brand-600 hover:text-brand-700">
                                        Invoice #{{ $payment['invoice_id'] }}
                                    </a>
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

            <x-pagination :response="$response" />
        @endif
    </div>
@endsection
