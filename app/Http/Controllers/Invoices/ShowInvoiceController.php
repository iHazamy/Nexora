<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\InvoiceApi;
use App\Runovia\Resources\PaymentApi;
use Illuminate\Contracts\View\View;

/**
 * GET /invoices/{invoice}
 *
 * ! The detailed read returns the invoice with its items, its payments, the joined
 *   customer, the joined bank account and the balance — everything this page needs in one
 *   call. Do not add per-item lookups.
 */
class ShowInvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceApi $invoices,
        private readonly PaymentApi $payments,
    ) {
    }

    public function __invoke(int $invoice): View
    {
        return view('invoices.show', [
            'invoice' => $this->invoices->find($invoice),

            /*
             * ! The payment methods, for the "record a payment" form on this page. Taken
             *   from PaymentApi rather than hard-coded in the view, so the list cannot
             *   drift from the values the API accepts.
             */
            'methods' => $this->payments->methodLabels(),
        ]);
    }
}
