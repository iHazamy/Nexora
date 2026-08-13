<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\CustomerApi;
use App\Runovia\Resources\InvoiceApi;
use Illuminate\Contracts\View\View;

/**
 * GET /customers/{customer}
 *
 * ! A customer belonging to another business answers 404, not 403 — the API refuses to
 *   confirm that an id exists outside your tenant. ResourceApi turns that into an
 *   ApiException and the handler into a 404 page, so this controller needs no ownership
 *   check of its own and must not attempt one.
 */
class ShowCustomerController extends Controller
{
    public function __construct(
        private readonly CustomerApi $customers,
        private readonly InvoiceApi $invoices,
    ) {
    }

    public function __invoke(int $customer): View
    {
        /*
         * ? Two calls rather than one. The API has no "customer with their invoices"
         *   endpoint, and adding one would mean a second way to read an invoice list with
         *   its own filtering and paging to keep consistent. The invoice index already
         *   filters by customer_id, so this asks it that question.
         */
        $invoices = $this->invoices->list([
            'customer_id' => $customer,
            'sort'        => 'invoice_date',
            'direction'   => 'desc',
            'per_page'    => 10,
        ]);

        return view('customers.show', [
            'customer' => $this->customers->find($customer),
            'invoices' => $invoices->records(),
            'invoiceTotal' => $invoices->total(),
        ]);
    }
}
