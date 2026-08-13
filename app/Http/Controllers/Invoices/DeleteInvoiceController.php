<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\InvoiceApi;
use Illuminate\Http\RedirectResponse;

/**
 * DELETE /invoices/{invoice}
 *
 * ! ONE ENDPOINT, TWO OUTCOMES, AND THE API DECIDES WHICH:
 *
 *       a DRAFT with no payments  -> the row is DELETED
 *       anything else             -> the invoice is CANCELLED and kept
 *
 *   Deleting an issued invoice would destroy a financial record and leave a hole in the
 *   invoice numbering, so the API will not do it. It reports what it actually did in
 *   `data.action`, and this controller READS THAT rather than assuming — telling a user
 *   "invoice deleted" when it was in fact cancelled and is still on their list is exactly
 *   the sort of small lie that erodes trust in a billing tool.
 *
 * ! Deleting an already-cancelled invoice is a 409, handled centrally.
 */
class DeleteInvoiceController extends Controller
{
    public function __construct(private readonly InvoiceApi $invoices)
    {
    }

    public function __invoke(int $invoice): RedirectResponse
    {
        $response = $this->invoices->deleteOrCancel($invoice);
        $action   = $this->invoices->actionTaken($response);

        if ($action === 'deleted') {
            return redirect()
                ->route('invoices.index')
                ->with('success', 'Draft invoice deleted.');
        }

        /*
         * ! Cancelled invoices still exist, so the user goes back to the invoice rather
         *   than to the index — they can see the CANCELLED status for themselves, which is
         *   the honest confirmation.
         */
        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice cancelled. It stays on record and in the numbering.');
    }
}
