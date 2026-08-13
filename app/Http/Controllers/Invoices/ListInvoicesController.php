<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\CustomerApi;
use App\Runovia\Resources\InvoiceApi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * GET /invoices
 *
 * ! List rows come with `customer_name` and `paid_amount` already joined on by the API, so
 *   this page is ONE call. Enriching each row with a per-invoice lookup would turn a
 *   25-row page into 26 requests.
 */
class ListInvoicesController extends Controller
{
    private const SORTABLE = ['invoice_number', 'invoice_date', 'due_date', 'event_date', 'total', 'status', 'id'];

    public function __construct(
        private readonly InvoiceApi $invoices,
        private readonly CustomerApi $customers,
    ) {
    }

    public function __invoke(Request $request): View
    {
        $sort = (string) $request->query('sort', 'invoice_date');

        $response = $this->invoices->list([
            'search'      => $request->query('search'),
            'status'      => $request->query('status'),
            'customer_id' => $request->query('customer_id'),
            'sort'        => in_array($sort, self::SORTABLE, true) ? $sort : 'invoice_date',
            'direction'   => $request->query('direction') === 'asc' ? 'asc' : 'desc',
            'page'        => $request->query('page'),
        ]);

        return view('invoices.index', [
            'response'  => $response,
            'invoices'  => $response->records(),
            'statuses'  => $this->invoices->allStatuses(),

            // # For the customer filter. One extra call, and only so the filter can show
            // # names instead of ids.
            'customers' => $this->customers->all(),
        ]);
    }
}
