<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\InvoiceApi;
use App\Runovia\Resources\ReportApi;
use Illuminate\Contracts\View\View;

/**
 * GET / — the business dashboard.
 *
 * ! THE MONEY FIGURES COME FROM ONE AGGREGATE ENDPOINT, not from summing a page of invoices.
 *   That distinction is the whole reason /reports/summary was added to the API: this app
 *   only ever sees one page of invoices at a time, so summing what it has would produce a
 *   figure that silently means "total of the most recent 25" — and summing fixed-2 decimal
 *   strings in PHP floats would be wrong even if it had them all. The API aggregates in SQL,
 *   in integer cents, across every invoice the business owns.
 *
 * ! Two calls, and each earns its place: the summary for the figures, and one small invoice
 *   list for "what needs attention". They are not merged, because a dashboard endpoint
 *   returning rows would be a second way to read invoices with its own sorting to keep
 *   consistent with the real one.
 */
class ShowDashboardController extends Controller
{
    public function __construct(
        private readonly ReportApi $reports,
        private readonly InvoiceApi $invoices,
    ) {
    }

    public function __invoke(): View
    {
        /*
         * ? Sorted by due date ascending, so the oldest unpaid thing is first — that is the
         *   question a dashboard is being asked. Not filtered by status: an invoice can be
         *   SENT or PARTIALLY_PAID and both need chasing, and the API has no "unpaid" filter
         *   because unpaid is a fact about money rather than a status.
         */
        $recent = $this->invoices->list([
            'sort'      => 'invoice_date',
            'direction' => 'desc',
            'per_page'  => 6,
        ]);

        return view('dashboard', [
            'summary' => $this->reports->summary(),
            'recent'  => $recent->records(),
        ]);
    }
}
