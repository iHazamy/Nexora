<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\BankAccountApi;
use App\Runovia\Resources\CustomerApi;
use App\Runovia\Resources\InvoiceApi;
use App\Runovia\Resources\PackageApi;
use App\Runovia\Resources\ProductApi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * GET /invoices/create
 *
 * ! Four calls to fill the pickers, and they are unavoidable: the API has no combined
 *   "everything needed to draft an invoice" endpoint, and inventing one would mean a
 *   second way to read customers, products, packages and bank accounts with its own
 *   filtering to keep consistent. They are small, capped reads.
 *
 * ! Products, packages and bank accounts are filtered to ACTIVE. Deactivating one is
 *   exactly the act of saying "do not put this on new invoices"; issued invoices that
 *   already reference it are unaffected because the API stores a copy of the price and
 *   description at issue time.
 */
class CreateInvoiceFormController extends Controller
{
    public function __construct(
        private readonly CustomerApi $customers,
        private readonly ProductApi $products,
        private readonly PackageApi $packages,
        private readonly BankAccountApi $bankAccounts,
        private readonly InvoiceApi $invoices,
    ) {
    }

    public function __invoke(Request $request): View
    {
        $bankAccounts = $this->bankAccounts->selectable();

        return view('invoices.form', [
            'invoice'      => null,
            'customers'    => $this->customers->all(),
            'products'     => $this->products->selectable(),
            'packages'     => $this->packages->selectable(),
            'bankAccounts' => $bankAccounts,
            'statuses'     => $this->invoices->settableStatuses(),

            /*
             * ! Pre-selects the customer when arriving from their page
             *   (/invoices/create?customer_id=4). Validated as an integer only — whether it
             *   is a customer of THIS business is the API's question, and it answers a
             *   foreign id with a 400.
             */
            'presetCustomerId' => $request->integer('customer_id') ?: null,

            /*
             * ? Pre-selects the first active bank account. There is deliberately no
             *   "default account" flag on the API — which account an invoice names is
             *   recorded on the invoice itself, so a default here is only ever a form
             *   pre-fill, and a stored flag would need a transaction to move and could end
             *   up set on two rows or none.
             */
            'presetBankAccountId' => $bankAccounts[0]['id'] ?? null,
        ]);
    }
}
