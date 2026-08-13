@extends('layouts.app')

@php
    use App\Support\DisplayDate;
    use App\Support\Money;

    $isEdit = $invoice !== null;
    $locked = $lockedFinancials ?? false;

    /*
     * ! The catalog is serialised once for the Alpine editor so picking a product fills
     *   its price without a round trip. Only id / name / price — nothing here is
     *   authoritative, and the API re-reads the real price and re-derives every total
     *   server-side when the form is submitted.
     */
    $catalog = [
        'products' => array_map(static fn (array $p): array => [
            'id' => $p['id'], 'name' => $p['name'], 'price' => $p['price'],
        ], $products),
        'packages' => array_map(static fn (array $p): array => [
            'id' => $p['id'], 'name' => $p['name'], 'price' => $p['price'],
        ], $packages),
    ];

    /*
     * ! Repopulates from old() first so a refused submission does not lose the user's
     *   lines. The API's 400 comes back through the central handler with withInput(), so
     *   old('items') is the edited state, not the stored one.
     */
    $lines = old('items') ?? array_map(static fn (array $item): array => [
        'description' => $item['description'],
        'quantity'    => Money::input($item['quantity']),
        'unit_price'  => Money::input($item['unit_price']),
        'discount'    => Money::input($item['discount'] ?? '0.00'),
        'product_id'  => $item['product_id'] ?? '',
        'package_id'  => $item['package_id'] ?? '',
    ], $invoice['items'] ?? []);
@endphp

@section('title', $isEdit ? 'Edit ' . $invoice['invoice_number'] : 'New invoice')

@section('content')
    <div class="mb-6">
        <a href="{{ $isEdit ? route('invoices.show', $invoice['id']) : route('invoices.index') }}"
           class="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
            <x-icon name="chevron-left" class="h-3.5 w-3.5" />
            {{ $isEdit ? 'Back to invoice' : 'Back to invoices' }}
        </a>
        <h1 class="page-title">
            {{ $isEdit ? 'Edit invoice ' . $invoice['invoice_number'] : 'New invoice' }}
        </h1>
    </div>

    @if ($locked)
        {{--
            ! Explained BEFORE the user starts typing, not discovered on submit. The API
              refuses any change to the items, discount, tax or customer of an invoice with
              payments against it, and finding that out after twenty minutes of editing
              would lose the work.
        --}}
        <div class="mb-5 alert-warning">
            <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0" />
            <div>
                <p class="font-medium">This invoice has payments recorded.</p>
                <p class="mt-0.5">
                    Its items, discount, tax and customer are locked so the outstanding balance
                    cannot move under the customer. You can still change the dates, the invoice
                    number, who it is addressed to, the bank account and the notes.
                    To change the amounts, cancel this invoice and raise a new one.
                </p>
            </div>
        </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('invoices.update', $invoice['id']) : route('invoices.store') }}"
          x-data="invoiceForm({
              lines: @js($lines),
              catalog: @js($catalog),
              invoiceDiscount: @js(old('discount', Money::input($invoice['discount'] ?? '0.00'))),
              invoiceTax: @js(old('tax', Money::input($invoice['tax'] ?? '0.00'))),
          })">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- ------------------------------------------------------------- Header --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="card">
                    <div class="card-header"><h2 class="card-title">Details</h2></div>
                    <div class="card-body grid gap-5 sm:grid-cols-2">

                        @if ($locked)
                            {{-- ! Rendered as text with NO input and NO name, so `customer_id`
                                 ! is never submitted. Its mere presence in the body is what
                                 ! the API's lock tests for — an unchanged value still 409s. --}}
                            <div class="field sm:col-span-2">
                                <span class="field-label">Customer</span>
                                <p class="input bg-slate-50 text-slate-600">
                                    {{ $invoice['customer']['name'] ?? '—' }}
                                </p>
                                <p class="field-hint">Locked — this invoice has payments.</p>
                            </div>
                        @else
                            <div class="sm:col-span-2">
                                <x-form.select
                                    name="customer_id"
                                    label="Customer"
                                    :options="collect($customers)->pluck('name', 'id')->all()"
                                    :value="$presetCustomerId"
                                    placeholder="Choose a customer…"
                                    required
                                />
                            </div>
                        @endif

                        <x-form.input
                            name="invoice_number"
                            label="Invoice number"
                            :value="$invoice['invoice_number'] ?? null"
                            hint="Leave blank to generate one automatically."
                            placeholder="INV-2026-0001"
                        />

                        <x-form.select
                            name="status"
                            label="Status"
                            :options="collect($statuses)->mapWithKeys(fn ($s) => [$s => Str::headline(Str::lower($s))])->all()"
                            :value="$invoice['status'] ?? 'DRAFT'"
                            hint="Paid and part paid are set automatically from payments."
                        />

                        <x-form.input
                            name="invoice_date"
                            label="Invoice date"
                            type="date"
                            :value="DisplayDate::input($invoice['invoice_date'] ?? now()->format('Y-m-d'))"
                            required
                        />

                        <x-form.input
                            name="due_date"
                            label="Due date"
                            type="date"
                            :value="DisplayDate::input($invoice['due_date'] ?? null)"
                            hint="Cannot be before the invoice date."
                        />

                        <x-form.input
                            name="event_date"
                            label="Event date"
                            type="date"
                            :value="DisplayDate::input($invoice['event_date'] ?? null)"
                            hint="When the booking actually happens. May be before or after the invoice date."
                        />

                        <x-form.input
                            name="attention"
                            label="Attention"
                            :value="$invoice['attention'] ?? null"
                            hint="Who to address it to, if the customer is a company."
                            placeholder="Ms Aisyah, Events Manager"
                        />

                        <div class="sm:col-span-2">
                            @if ($bankAccounts === [])
                                <div class="alert-info">
                                    <x-icon name="bank" class="mt-0.5 h-4 w-4 shrink-0" />
                                    <span>
                                        No bank account set up yet, so this invoice will not show payment
                                        details.
                                        @if ($apiSession->canWrite('business', 'C'))
                                            <a href="{{ route('settings.edit') }}"
                                               class="font-medium underline">Add one in Settings</a>.
                                        @endif
                                    </span>
                                </div>
                            @else
                                <x-form.select
                                    name="bank_account_id"
                                    label="Pay into"
                                    :options="collect($bankAccounts)->mapWithKeys(fn ($a) => [
                                        $a['id'] => $a['bank_name'] . ' — ' . $a['account_number'],
                                    ])->all()"
                                    :value="$presetBankAccountId"
                                    placeholder="No payment details on this invoice"
                                    hint="Printed on the invoice so the customer knows where to pay."
                                />
                            @endif
                        </div>
                    </div>
                </div>

                {{-- -------------------------------------------------------- Line items --}}
                @include('invoices.partials.items', ['locked' => $locked])
            </div>

            {{-- ------------------------------------------------------------- Totals --}}
            <div class="space-y-6">
                <div class="card lg:sticky lg:top-20">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Totals</h2>
                            {{--
                                ! Labelled an estimate, and that label is load-bearing. These
                                  figures are computed in the browser for immediate feedback;
                                  the API recomputes everything in integer cents and its
                                  answer is what is saved and shown afterwards.
                            --}}
                            <p class="card-subtitle">Estimate — the saved figures come from the server.</p>
                        </div>
                    </div>

                    <div class="card-body space-y-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">Subtotal</span>
                            <span class="font-medium text-slate-900 tabular"
                                  x-text="'{{ Money::CURRENCY }} ' + subtotal"></span>
                        </div>

                        @if ($locked)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">Discount</span>
                                <span class="text-slate-900 tabular">
                                    &minus; {{ Money::format($invoice['discount']) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">Tax</span>
                                <span class="text-slate-900 tabular">
                                    {{ Money::format($invoice['tax']) }}
                                </span>
                            </div>
                        @else
                            <x-form.input
                                name="discount"
                                label="Invoice discount"
                                type="number"
                                step="0.01"
                                min="0"
                                :value="Money::input($invoice['discount'] ?? '0.00')"
                                :prefix="Money::CURRENCY"
                                hint="Off the whole bill, after any line discounts."
                                x-model="invoiceDiscount"
                            />

                            <x-form.input
                                name="tax"
                                label="Tax"
                                type="number"
                                step="0.01"
                                min="0"
                                :value="Money::input($invoice['tax'] ?? '0.00')"
                                :prefix="Money::CURRENCY"
                                x-model="invoiceTax"
                            />
                        @endif

                        <div class="border-t border-slate-200 pt-4">
                            <div class="flex items-baseline justify-between">
                                <span class="text-sm font-semibold text-slate-900">Total</span>
                                <span class="text-xl font-bold tracking-tight text-slate-900 tabular"
                                      x-text="'{{ Money::CURRENCY }} ' + total"></span>
                            </div>
                        </div>

                        {{-- Anything the API is going to refuse, surfaced before submitting. --}}
                        <template x-if="warnings.length > 0">
                            <div class="alert-warning">
                                <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0" />
                                <ul class="space-y-1">
                                    <template x-for="warning in warnings" :key="warning">
                                        <li x-text="warning"></li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>

                    <div class="border-t border-slate-200/80 px-5 py-4 sm:px-6">
                        <button type="submit" class="btn-primary w-full">
                            {{ $isEdit ? 'Save changes' : 'Create invoice' }}
                        </button>
                        <a href="{{ $isEdit ? route('invoices.show', $invoice['id']) : route('invoices.index') }}"
                           class="btn-secondary mt-2 w-full">Cancel</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <x-form.textarea
                            name="notes"
                            label="Notes"
                            rows="4"
                            :value="$invoice['notes'] ?? null"
                            hint="Shown on the invoice."
                            placeholder="Deposit due on booking."
                        />
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
