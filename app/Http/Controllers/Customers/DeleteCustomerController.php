<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\CustomerApi;
use Illuminate\Http\RedirectResponse;

/**
 * DELETE /customers/{customer}
 *
 * ! A customer with invoices CANNOT be deleted — the API answers 409 because removing
 *   them would orphan financial records. That refusal is not handled here: the handler
 *   bounces it back to the previous page with the API's own message, which explains the
 *   reason. Pre-checking for invoices in this controller would be a second, weaker copy
 *   of a rule the API already enforces atomically.
 */
class DeleteCustomerController extends Controller
{
    public function __construct(private readonly CustomerApi $customers)
    {
    }

    public function __invoke(int $customer): RedirectResponse
    {
        $this->customers->delete($customer);

        // ! Back to the index, not to the customer that no longer exists.
        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer deleted.');
    }
}
