{{--
    The printable invoice.

    ! NO APP LAYOUT, and no PDF library. This is a standalone page with a print stylesheet;
      the viewer's own browser print dialog produces the PDF.

    ? The old app rendered its PDF through dompdf, which meant maintaining a second layout
      in a second engine's dialect of CSS — and the PDF never quite matched what the screen
      showed. It is also a native-ish dependency to keep patched on the host, which for an
      ARM deployment is one more thing needing an arm64 build. One layout, printed.

    ! Every figure here comes from the API. Nothing on this page adds anything up.
--}}
@php
    use App\Support\DisplayDate;
    use App\Support\Money;

    $outstanding = $invoice['outstanding'] ?? '0.00';
    $paid        = $invoice['paid_amount'] ?? '0.00';
    $isSettled   = Money::isZero($outstanding);

    // ! Only a relative storage path ever reaches here — the API refuses anything else on
    // ! the way in — so it is safe to hand to the asset helper.
    $logo = ! empty($business['logo_path']) ? Storage::disk('public')->url($business['logo_path']) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice['invoice_number'] }} — {{ $business['name'] ?? config('app.name') }}</title>
    @vite('resources/css/app.css')
    <style>
        /*
         * ! `print-color-adjust: exact` keeps the status badge and the totals panel from
         *   printing as white boxes. Browsers strip background colours when printing unless
         *   told not to, and a "PAID" badge with no fill reads as an empty outline.
         */
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .sheet { box-shadow: none !important; border: 0 !important; margin: 0 !important; max-width: none !important; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 14mm; }
        }
    </style>
</head>
<body class="bg-slate-100 py-8">

    <div class="no-print mx-auto mb-4 flex max-w-[210mm] items-center justify-between gap-3 px-4">
        <a href="{{ route('invoices.show', $invoice['id']) }}" class="btn-secondary btn-sm">
            <x-icon name="chevron-left" class="h-3.5 w-3.5" />
            Back to invoice
        </a>
        <button type="button" onclick="window.print()" class="btn-primary btn-sm">
            <x-icon name="download" class="h-3.5 w-3.5" />
            Print or save as PDF
        </button>
    </div>

    <div class="sheet mx-auto max-w-[210mm] bg-white p-10 shadow-sm">

        {{-- ------------------------------------------------------------ Letterhead --}}
        <div class="flex items-start justify-between gap-8 border-b border-slate-200 pb-8">
            <div>
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $business['name'] ?? '' }}" class="mb-3 h-14 w-auto">
                @endif
                <p class="text-lg font-bold tracking-tight text-slate-900">
                    {{ $business['name'] ?? config('app.name') }}
                </p>
                @if (! empty($business['address']))
                    <p class="mt-1 whitespace-pre-line text-xs leading-relaxed text-slate-600">
                        {{ $business['address'] }}</p>
                @endif
                <p class="mt-1 text-xs text-slate-600">
                    @if (! empty($business['phone'])) {{ $business['phone'] }} @endif
                    @if (! empty($business['email'])) &middot; {{ $business['email'] }} @endif
                </p>
                @if (! empty($business['registration_number']))
                    <p class="mt-1 text-xs text-slate-500">
                        Reg. no. {{ $business['registration_number'] }}
                    </p>
                @endif
            </div>

            <div class="shrink-0 text-right">
                <p class="text-2xl font-bold tracking-tight text-slate-900">INVOICE</p>
                <p class="mt-1 font-mono text-sm text-slate-700">{{ $invoice['invoice_number'] }}</p>
                <div class="mt-3">
                    <x-status-badge :status="$invoice['status']"
                                    :overdue="DisplayDate::isOverdue($invoice['due_date'] ?? null)" />
                </div>
            </div>
        </div>

        {{-- ---------------------------------------------------------------- Parties --}}
        <div class="grid grid-cols-2 gap-8 py-8">
            <div>
                <p class="metric-label">Billed to</p>
                <p class="mt-2 font-semibold text-slate-900">{{ $invoice['customer']['name'] ?? '—' }}</p>
                @if (! empty($invoice['attention']))
                    <p class="text-sm text-slate-700">Attn: {{ $invoice['attention'] }}</p>
                @endif
                @if (! empty($invoice['customer']['address']))
                    <p class="mt-1 whitespace-pre-line text-xs leading-relaxed text-slate-600">
                        {{ $invoice['customer']['address'] }}</p>
                @endif
                @if (! empty($invoice['customer']['phone']))
                    <p class="mt-1 text-xs text-slate-600">{{ $invoice['customer']['phone'] }}</p>
                @endif
                @if (! empty($invoice['customer']['email']))
                    <p class="text-xs text-slate-600">{{ $invoice['customer']['email'] }}</p>
                @endif
            </div>

            <div class="space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <span class="text-slate-500">Invoice date</span>
                    <span class="font-medium text-slate-900">{{ DisplayDate::date($invoice['invoice_date']) }}</span>
                </div>
                @if (! empty($invoice['due_date']))
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Due date</span>
                        <span class="font-medium text-slate-900">{{ DisplayDate::date($invoice['due_date']) }}</span>
                    </div>
                @endif
                {{-- The event date is often the single most important line on the page for a
                     venue customer, so it is not buried. --}}
                @if (! empty($invoice['event_date']))
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Event date</span>
                        <span class="font-medium text-slate-900">{{ DisplayDate::date($invoice['event_date']) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- ------------------------------------------------------------------ Items --}}
        <table class="w-full text-sm">
            <thead>
                <tr class="border-y border-slate-300">
                    <th class="py-2 text-left font-semibold text-slate-700">Description</th>
                    <th class="py-2 text-right font-semibold text-slate-700">Qty</th>
                    <th class="py-2 text-right font-semibold text-slate-700">Unit price</th>
                    <th class="py-2 text-right font-semibold text-slate-700">Discount</th>
                    <th class="py-2 text-right font-semibold text-slate-700">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice['items'] as $item)
                    <tr class="border-b border-slate-100">
                        <td class="py-2.5 pr-4 text-slate-800">{{ $item['description'] }}</td>
                        <td class="py-2.5 text-right text-slate-600 tabular">{{ $item['quantity'] }}</td>
                        <td class="py-2.5 text-right text-slate-600 tabular">
                            {{ Money::amount($item['unit_price']) }}</td>
                        <td class="py-2.5 text-right text-slate-600 tabular">
                            {{ Money::isZero($item['discount'] ?? '0.00') ? '—' : '− ' . Money::amount($item['discount']) }}
                        </td>
                        <td class="py-2.5 text-right font-medium text-slate-900 tabular">
                            {{ Money::amount($item['line_total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- ----------------------------------------------------------------- Totals --}}
        <div class="mt-6 flex justify-end">
            <div class="w-full max-w-xs space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-600">Subtotal</span>
                    <span class="text-slate-900 tabular">{{ Money::amount($invoice['subtotal']) }}</span>
                </div>
                @if (! Money::isZero($invoice['discount']))
                    <div class="flex justify-between">
                        <span class="text-slate-600">Discount</span>
                        <span class="text-slate-900 tabular">&minus; {{ Money::amount($invoice['discount']) }}</span>
                    </div>
                @endif
                @if (! Money::isZero($invoice['tax']))
                    <div class="flex justify-between">
                        <span class="text-slate-600">Tax</span>
                        <span class="text-slate-900 tabular">{{ Money::amount($invoice['tax']) }}</span>
                    </div>
                @endif

                <div class="flex justify-between border-t border-slate-300 pt-2 text-base font-bold">
                    <span class="text-slate-900">Total</span>
                    <span class="text-slate-900 tabular">{{ Money::format($invoice['total']) }}</span>
                </div>

                @if (! Money::isZero($paid))
                    <div class="flex justify-between">
                        <span class="text-slate-600">Paid</span>
                        <span class="text-emerald-700 tabular">&minus; {{ Money::amount($paid) }}</span>
                    </div>
                    <div @class([
                        'flex justify-between rounded-md px-3 py-2 text-base font-bold',
                        'bg-emerald-50 text-emerald-800' => $isSettled,
                        'bg-amber-50 text-amber-800' => ! $isSettled,
                    ])>
                        <span>{{ $isSettled ? 'Paid in full' : 'Balance due' }}</span>
                        <span class="tabular">{{ Money::format($outstanding) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- -------------------------------------------------------- Payment details --}}
        @if (! empty($invoice['bank_account']))
            <div class="mt-8 rounded-lg bg-slate-50 p-4">
                <p class="metric-label">Payment details</p>
                <div class="mt-2 text-sm">
                    <p class="font-medium text-slate-900">{{ $invoice['bank_account']['bank_name'] }}</p>
                    {{-- ! Rendered as the string it is. An account number with leading zeros is
                         ! destroyed by any numeric cast. --}}
                    <p class="text-slate-700 tabular">{{ $invoice['bank_account']['account_number'] }}</p>
                    @if (! empty($invoice['bank_account']['account_holder']))
                        <p class="text-slate-600">{{ $invoice['bank_account']['account_holder'] }}</p>
                    @endif
                </div>
            </div>
        @endif

        @if (! empty($invoice['notes']))
            <div class="mt-6">
                <p class="metric-label">Notes</p>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $invoice['notes'] }}</p>
            </div>
        @endif

        @if (! empty($business['invoice_terms']))
            <div class="mt-6 border-t border-slate-200 pt-4">
                <p class="metric-label">Terms</p>
                <p class="mt-1 whitespace-pre-line text-xs leading-relaxed text-slate-600">
                    {{ $business['invoice_terms'] }}</p>
            </div>
        @endif
    </div>
</body>
</html>
