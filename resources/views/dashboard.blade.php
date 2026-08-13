@extends('layouts.app')

@section('title', 'Dashboard')

@php
    use App\Support\DisplayDate;
    use App\Support\Money;

    $money    = $summary['money'];
    $counts   = $summary['invoices'];
    $overdue  = $summary['overdue'];
    $isEmpty  = ($counts['total'] ?? 0) === 0;
@endphp

@section('content')
    <div class="mb-6">
        <h1 class="page-title">Good day, {{ Str::before($apiSession->userName(), ' ') }}</h1>
        <p class="page-subtitle">Here is where {{ $apiSession->businessName() }} stands.</p>
    </div>

    @if ($isEmpty)
        {{-- A brand-new business. Not an error state — a first-run one. --}}
        <div class="card">
            <x-empty-state
                icon="invoice"
                title="Nothing invoiced yet"
                text="Add a customer, then raise your first invoice. Products and packages are optional — you can type one-off lines instead."
            >
                <div class="flex flex-wrap justify-center gap-2">
                    @if ($apiSession->canCreate('customers'))
                        <a href="{{ route('customers.create') }}" class="btn-primary btn-sm">
                            <x-icon name="plus" class="h-4 w-4" />
                            Add a customer
                        </a>
                    @endif
                    @if ($apiSession->canCreate('invoices'))
                        <a href="{{ route('invoices.create') }}" class="btn-secondary btn-sm">New invoice</a>
                    @endif
                </div>
            </x-empty-state>
        </div>
    @else
        {{-- ------------------------------------------------------------- Figures --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="card"><div class="card-body">
                <p class="metric-label">Invoiced</p>
                <p class="metric-value">{{ Money::format($money['invoiced']) }}</p>
                <p class="metric-note">
                    {{ $counts['total'] }} {{ Str::plural('invoice', $counts['total']) }}
                    @if (($counts['cancelled'] ?? 0) > 0)
                        &middot; excludes {{ $counts['cancelled'] }} cancelled
                    @endif
                </p>
            </div></div>

            <div class="card"><div class="card-body">
                <p class="metric-label">Collected</p>
                <p class="metric-value text-emerald-700">{{ Money::format($money['collected']) }}</p>
                <p class="metric-note">Payments recorded to date</p>
            </div></div>

            <div class="card"><div class="card-body">
                <p class="metric-label">Outstanding</p>
                <p @class([
                    'metric-value',
                    'text-slate-400' => Money::isZero($money['outstanding']),
                ])>{{ Money::format($money['outstanding']) }}</p>
                <p class="metric-note">
                    {{ ($counts['sent'] ?? 0) + ($counts['partially_paid'] ?? 0) }} awaiting payment
                </p>
            </div></div>

            <div @class(['card', 'ring-1 ring-rose-200' => $overdue['count'] > 0])>
                <div class="card-body">
                    <p class="metric-label">Overdue</p>
                    <p @class([
                        'metric-value',
                        'text-rose-700'  => $overdue['count'] > 0,
                        'text-slate-400' => $overdue['count'] === 0,
                    ])>{{ Money::format($overdue['amount']) }}</p>
                    <p class="metric-note">
                        @if ($overdue['count'] > 0)
                            <span class="text-rose-600">
                                {{ $overdue['count'] }} {{ Str::plural('invoice', $overdue['count']) }} past due
                            </span>
                        @else
                            Nothing past due
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- ------------------------------------------------------ Recent work --}}
            <div class="lg:col-span-2">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Recent invoices</h2>
                            <p class="card-subtitle">The six most recently raised</p>
                        </div>
                        <a href="{{ route('invoices.index') }}" class="btn-secondary btn-sm">View all</a>
                    </div>

                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Invoice</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="numeric text-right">Outstanding</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recent as $invoice)
                                    @php
                                        $rowOutstanding = $invoice['outstanding'] ?? '0.00';
                                        $rowOverdue = DisplayDate::isOverdue($invoice['due_date'] ?? null);
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('invoices.show', $invoice['id']) }}"
                                               class="font-medium text-slate-900 hover:text-brand-600">
                                                {{ $invoice['invoice_number'] }}
                                            </a>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                {{ DisplayDate::date($invoice['invoice_date']) }}
                                            </p>
                                        </td>
                                        <td class="text-slate-700">{{ $invoice['customer_name'] ?? '—' }}</td>
                                        <td>
                                            <x-status-badge :status="$invoice['status']" :overdue="$rowOverdue" />
                                        </td>
                                        <td class="numeric text-right">
                                            <span @class([
                                                'font-medium',
                                                'text-slate-400' => Money::isZero($rowOutstanding),
                                                'text-slate-900' => ! Money::isZero($rowOutstanding),
                                            ])>{{ Money::amount($rowOutstanding) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------------------- Breakdown --}}
            <div class="space-y-6">
                <div class="card">
                    <div class="card-header"><h2 class="card-title">By status</h2></div>
                    <div class="card-body space-y-3">
                        @foreach (['draft' => 'DRAFT', 'sent' => 'SENT', 'partially_paid' => 'PARTIALLY_PAID', 'paid' => 'PAID', 'cancelled' => 'CANCELLED'] as $key => $status)
                            <a href="{{ route('invoices.index', ['status' => $status]) }}"
                               class="flex items-center justify-between rounded-lg px-2 py-1.5 -mx-2 transition hover:bg-slate-50">
                                <x-status-badge :status="$status" />
                                <span class="text-sm font-semibold text-slate-900 tabular">
                                    {{ $counts[$key] ?? 0 }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                @if ($apiSession->canCreate('invoices'))
                    <div class="card">
                        <div class="card-body space-y-2">
                            <a href="{{ route('invoices.create') }}" class="btn-primary w-full">
                                <x-icon name="plus" class="h-4 w-4" />
                                New invoice
                            </a>
                            <a href="{{ route('customers.create') }}" class="btn-secondary w-full">
                                Add a customer
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection
