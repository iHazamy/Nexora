<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\UpdateInvoiceRequest;
use App\Runovia\Resources\InvoiceApi;
use Illuminate\Http\RedirectResponse;

/**
 * PUT /invoices/{invoice}
 *
 * ! `items` IS SENT ONLY WHEN THE FORM SUBMITTED IT, and that distinction is the whole
 *   subtlety of this action. To the API:
 *
 *       key absent  -> leave the existing lines exactly as they are
 *       key present -> replace every line and recompute all the totals
 *
 *   The edit form omits the key entirely when the invoice has payments (its lines are
 *   locked and rendered read-only), which is what allows a user to correct the notes or
 *   the due date on a part-paid invoice. Defaulting to `[]` here would empty the lines of
 *   every such invoice; defaulting to the existing lines would need this app to hold a
 *   copy of them, which it must not.
 */
class UpdateInvoiceController extends Controller
{
    public function __construct(private readonly InvoiceApi $invoices)
    {
    }

    public function __invoke(UpdateInvoiceRequest $request, int $invoice): RedirectResponse
    {
        $payload = $request->payload();
        $items   = $request->items();

        if ($items !== null) {
            $payload['items'] = $items;
        }

        $this->invoices->update($invoice, $payload);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated.');
    }
}
