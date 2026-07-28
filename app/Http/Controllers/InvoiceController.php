<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Package;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View { return view('invoices.index', ['invoices' => Invoice::latest()->get()]); }
    public function create(): View { return view('invoices.form', ['invoice' => null, 'source' => null, 'packages' => Package::orderBy('name')->get(), 'services' => Service::orderBy('name')->get(), 'bankAccounts' => BankAccount::orderBy('bank_name')->get(), 'items' => [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'discount' => 0]], 'nextNumber' => $this->nextNumber()]); }
    public function show(Invoice $invoice): View { return view('invoices.show', compact('invoice')); }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $invoice = DB::transaction(function () use ($data) {
            $totals = $this->totals($data['items'], $data['deposit'] ?? 0);
            $totals['paid_at'] = $totals['balance'] <= 0 ? now() : null;
            $invoice = Invoice::create([...collect($data)->except('items')->all(), 'number' => $this->nextNumber(), ...$totals]);
            $this->saveItems($invoice, $data['items']);
            return $invoice;
        });
        return redirect()->route('invoices.show', $invoice)->with('success', "Invoice {$invoice->number} created.");
    }

    public function edit(Invoice $invoice): View { return view('invoices.form', ['invoice' => $invoice, 'source' => null, 'packages' => Package::orderBy('name')->get(), 'services' => Service::orderBy('name')->get(), 'bankAccounts' => BankAccount::orderBy('bank_name')->get(), 'items' => $invoice->items->map->only(['description', 'quantity', 'unit_price', 'discount'])->all(), 'nextNumber' => null]); }
    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $this->validated($request); $totals = $this->totals($data['items'], $data['deposit'] ?? 0);
        $totals['paid_at'] = $totals['balance'] <= 0 ? ($invoice->paid_at ?? now()) : null;
        DB::transaction(function () use ($invoice, $data, $totals) { $invoice->update([...collect($data)->except('items')->all(), ...$totals]); $invoice->items()->delete(); $this->saveItems($invoice, $data['items']); });
        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function duplicate(Invoice $invoice): View { return view('invoices.form', ['invoice' => null, 'packages' => Package::orderBy('name')->get(), 'services' => Service::orderBy('name')->get(), 'bankAccounts' => BankAccount::orderBy('bank_name')->get(), 'items' => $invoice->items->map->only(['description', 'quantity', 'unit_price', 'discount'])->all(), 'nextNumber' => $this->nextNumber(), 'source' => $invoice]); }
    public function markPaid(Invoice $invoice): RedirectResponse { $invoice->update(['deposit' => $invoice->grand_total, 'balance' => 0, 'paid_at' => $invoice->paid_at ?? now()]); return back()->with('success', 'Invoice marked as paid in full.'); }
    public function destroy(Invoice $invoice): RedirectResponse { $invoice->delete(); return redirect()->route('invoices.index')->with('success', 'Invoice deleted.'); }
    public function pdf(Invoice $invoice)
    {
        $invoice->load('items', 'bankAccount');
        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice, 'settings' => $this->settingsWithLogo()])->setPaper('a4')->download($invoice->number.'.pdf');
    }
    public function receipt(Invoice $invoice)
    {
        abort_unless((float) $invoice->balance <= 0, 404);
        $invoice->load('items', 'bankAccount');
        $receiptNumber = 'RCT-'.substr($invoice->number, 4);
        return Pdf::loadView('invoices.receipt', ['invoice' => $invoice, 'settings' => $this->settingsWithLogo(), 'receiptNumber' => $receiptNumber])->setPaper('a4')->download($receiptNumber.'.pdf');
    }
    private function settingsWithLogo(): array
    {
        $settings = Setting::values();
        if (!empty($settings['logo'])) {
            $path = storage_path('app/public/'.$settings['logo']);
            $settings['logo_data'] = is_file($path) ? 'data:image/'.pathinfo($path, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($path)) : null;
        }
        return $settings;
    }
    private function validated(Request $request): array { return $request->validate(['customer_name' => ['required','string','max:255'], 'attention' => ['nullable','string','max:255'], 'customer_phone' => ['nullable','string','max:50'], 'invoice_date' => ['required','date'], 'event_date' => ['nullable','date'], 'due_date' => ['nullable','date'], 'bank_account_id' => ['nullable','exists:bank_accounts,id'], 'deposit' => ['nullable','numeric','min:0'], 'items' => ['required','array','min:1'], 'items.*.description' => ['required','string','max:255'], 'items.*.quantity' => ['required','numeric','min:0.01'], 'items.*.unit_price' => ['required','numeric','min:0'], 'items.*.discount' => ['nullable','numeric','min:0']]); }
    private function lineTotal(array $item): float { return max(0, (float)$item['quantity'] * (float)$item['unit_price'] - (float)($item['discount'] ?? 0)); }
    private function totals(array $items, float $deposit): array {
        $subtotal = collect($items)->sum(fn ($item) => (float)$item['quantity'] * (float)$item['unit_price']);
        $discount = collect($items)->sum(fn ($item) => (float)($item['discount'] ?? 0));
        $total = max(0, $subtotal - $discount);
        return ['subtotal' => $subtotal, 'discount' => $discount, 'grand_total' => $total, 'deposit' => min($deposit, $total), 'balance' => max(0, $total - $deposit)];
    }
    private function saveItems(Invoice $invoice, array $items): void { foreach ($items as $i => $item) $invoice->items()->create(['description' => $item['description'], 'quantity' => $item['quantity'], 'unit_price' => $item['unit_price'], 'discount' => $item['discount'] ?? 0, 'total' => $this->lineTotal($item), 'sort_order' => $i]); }
    private function nextNumber(): string { return 'INV-'.str_pad(((int) Invoice::max('id')) + 1, 6, '0', STR_PAD_LEFT); }
}
