<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\BankAccountApi;
use App\Runovia\Resources\CustomerApi;
use App\Runovia\Resources\InvoiceApi;
use App\Runovia\Resources\PackageApi;
use App\Runovia\Resources\ProductApi;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * GET /invoices/{invoice}/edit
 *
 * ! WORKS OUT WHAT IS STILL EDITABLE, and that is this controller's real job.
 *
 *   Once ANY payment exists the API locks `items`, `discount`, `tax` and `customer_id`
 *   with a 409 — the outstanding balance must not move under a customer who has already
 *   paid against it. `notes`, `due_date`, `invoice_number`, `event_date`, `attention` and
 *   `bank_account_id` still change, because none of them is a figure.
 *
 *   The form is told which state it is in so it can render the locked fields read-only and
 *   submit no `items` key. Discovering the lock by submitting and being refused would lose
 *   whatever else the user had edited on the way.
 */
class EditInvoiceFormController extends Controller
{
    public function __construct(
        private readonly InvoiceApi $invoices,
        private readonly CustomerApi $customers,
        private readonly ProductApi $products,
        private readonly PackageApi $packages,
        private readonly BankAccountApi $bankAccounts,
    ) {
    }

    public function __invoke(int $invoice): View|RedirectResponse
    {
        $record = $this->invoices->find($invoice);

        /*
         * ! A CANCELLED invoice cannot be modified at all — every field, not just the
         *   financial ones. Sending the user to a form whose every submission is a 409 is
         *   worse than telling them here.
         */
        if (($record['status'] ?? '') === 'CANCELLED') {
            return redirect()
                ->route('invoices.show', $invoice)
                ->withErrors(['runovia' => 'A cancelled invoice cannot be edited.']);
        }

        // ! Compared on the money STRING, not by casting to float. Money::isZero() reads
        // ! the digits.
        $hasPayments = !Money::isZero($record['paid_amount'] ?? '0.00');

        return view('invoices.form', [
            'invoice'      => $record,
            'lockedFinancials' => $hasPayments,
            'customers'    => $this->customers->all(),
            'products'     => $this->products->selectable(),
            'packages'     => $this->packages->selectable(),
            'bankAccounts' => $this->bankAccounts->selectable(),
            'statuses'     => $this->invoices->settableStatuses(),
            'presetCustomerId'    => (int) ($record['customer_id'] ?? 0) ?: null,
            'presetBankAccountId' => $record['bank_account_id'] ?? null,
        ]);
    }
}
