<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\CustomerRequest;
use App\Runovia\Resources\CustomerApi;
use Illuminate\Http\RedirectResponse;

/**
 * PUT /customers/{customer}
 */
class UpdateCustomerController extends Controller
{
    public function __construct(private readonly CustomerApi $customers)
    {
    }

    public function __invoke(CustomerRequest $request, int $customer): RedirectResponse
    {
        $this->customers->update($customer, $request->payload());

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer updated.');
    }
}
