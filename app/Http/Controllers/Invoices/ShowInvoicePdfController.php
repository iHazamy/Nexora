<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\BusinessApi;
use App\Runovia\Resources\InvoiceApi;
use Illuminate\Contracts\View\View;

/**
 * GET /invoices/{invoice}/print
 *
 * ! RENDERS A PRINTABLE PAGE, NOT A GENERATED PDF FILE. The viewer's own browser print
 *   dialog produces the PDF.
 *
 * ? The old standalone app used dompdf. Dropping it is deliberate: it is a native-ish
 *   dependency to install and keep patched on the host — which for this deployment is an
 *   ARM Ampere instance where every binary dependency is one more thing needing an arm64
 *   build — and it renders a *different* engine's idea of the layout, so the PDF never
 *   quite matched the on-screen invoice. A print stylesheet means what you see is what
 *   prints, and the layout is maintained once.
 *
 * ! The business record is fetched for the letterhead — its name, address, registration
 *   number and payment terms. The bank account comes joined onto the invoice itself, so it
 *   is not fetched separately.
 */
class ShowInvoicePdfController extends Controller
{
    public function __construct(
        private readonly InvoiceApi $invoices,
        private readonly BusinessApi $businesses,
    ) {
    }

    public function __invoke(int $invoice): View
    {
        return view('invoices.print', [
            'invoice'  => $this->invoices->find($invoice),
            'business' => $this->businesses->current(),
        ]);
    }
}
