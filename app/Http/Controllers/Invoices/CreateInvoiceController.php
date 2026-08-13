<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\CreateInvoiceRequest;
use App\Runovia\Resources\InvoiceApi;
use Illuminate\Http\RedirectResponse;

/**
 * POST /invoices
 *
 * ! `items` is required on create and the API refuses an empty list, so unlike the update
 *   path this one always sends the key. `items() ?? []` makes the empty case reach the API
 *   and be refused there with its own message, rather than being silently turned into an
 *   invoice with no lines.
 *
 * ! The invoice number is left to the API when the field is blank — it generates
 *   INV-{year}-{0001}, sequential per business per year, and holds a unique index on
 *   (business_id, invoice_number) that is the real guarantee under concurrency. This app
 *   must never generate one itself; the old standalone app did it with `max(id) + 1`,
 *   which races and leaves gaps after a deletion.
 */
class CreateInvoiceController extends Controller
{
    public function __construct(private readonly InvoiceApi $invoices)
    {
    }

    public function __invoke(CreateInvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoices
            ->create($request->payload() + ['items' => $request->items() ?? []])
            ->record();

        return redirect()
            ->route('invoices.show', $invoice['id'])
            ->with('success', "Invoice {$invoice['invoice_number']} created.");
    }
}
