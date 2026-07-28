@extends('layouts.app')
@section('title','Invoice History')
@section('content')
<div x-data="invoiceList(@js($invoices->map(fn($invoice) => [
    'id' => $invoice->id,
    'number' => $invoice->number,
    'customer_name' => $invoice->customer_name,
    'date' => $invoice->invoice_date->format('d M Y'),
    'total' => (float) $invoice->grand_total,
    'balance' => (float) $invoice->balance,
    'status' => $invoice->status,
    'show_url' => route('invoices.show', $invoice),
    'pdf_url' => route('invoices.pdf', $invoice),
    'duplicate_url' => route('invoices.duplicate', $invoice),
    'destroy_url' => route('invoices.destroy', $invoice),
])))">
<div class="mb-6 flex flex-wrap items-center justify-between gap-3"><input type="text" x-model="search" placeholder="Search by invoice number or customer…" class="w-full max-w-sm rounded-lg border-slate-300 text-sm"><a href="{{ route('invoices.create') }}" class="btn-primary">+ New Invoice</a></div>
<div class="card overflow-x-auto p-0"><table class="w-full min-w-[720px] text-left text-sm"><thead><tr class="border-b bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><th class="px-6 py-3">Invoice number</th><th class="px-6 py-3">Customer</th><th class="px-6 py-3">Date</th><th class="px-6 py-3">Total</th><th class="px-6 py-3">Balance</th><th class="px-6 py-3">Status</th><th class="px-6 py-3"></th></tr></thead><tbody>
<template x-for="invoice in filtered" :key="invoice.id"><tr class="border-b border-slate-100"><td class="px-6 py-4 font-medium" x-text="invoice.number"></td><td class="px-6 py-4" x-text="invoice.customer_name"></td><td class="px-6 py-4" x-text="invoice.date"></td><td class="px-6 py-4" x-text="`RM ${invoice.total.toLocaleString('en-MY',{minimumFractionDigits:2,maximumFractionDigits:2})}`"></td><td class="px-6 py-4" x-text="`RM ${invoice.balance.toLocaleString('en-MY',{minimumFractionDigits:2,maximumFractionDigits:2})}`"></td><td class="px-6 py-4"><span class="badge" :class="invoice.status==='Paid' ? 'badge-paid' : 'badge-open'" x-text="invoice.status"></span></td><td class="px-6 py-4"><div class="flex gap-3 text-xs font-medium text-indigo-600"><a :href="invoice.show_url">View</a><a :href="invoice.pdf_url">PDF</a><a :href="invoice.duplicate_url">Duplicate</a><form method="POST" :action="invoice.destroy_url" onsubmit="return confirm('Delete this invoice?')">@csrf @method('DELETE')<button class="text-rose-600">Delete</button></form></div></td></tr></template>
<tr x-show="!invoices.length"><td colspan="7" class="px-6 py-12 text-center text-slate-500">No invoices saved yet.</td></tr>
<tr x-show="invoices.length && !filtered.length"><td colspan="7" class="px-6 py-12 text-center text-slate-500">No invoices match your search.</td></tr>
</tbody></table></div>
</div>
@endsection
