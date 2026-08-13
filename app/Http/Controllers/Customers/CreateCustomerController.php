<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\CustomerRequest;
use App\Runovia\Resources\CustomerApi;
use Illuminate\Http\RedirectResponse;

/**
 * POST /customers
 *
 * ! No try/catch and no success check. CustomerApi::create() throws on refusal and the
 *   handler in bootstrap/app.php decides what each response_code means — a 400 or 409
 *   goes back to this form with the API's message, a 604 goes to the login page, a 611
 *   goes to a contact-support page. Branching here would be that logic written a
 *   fourteenth time.
 */
class CreateCustomerController extends Controller
{
    public function __construct(private readonly CustomerApi $customers)
    {
    }

    public function __invoke(CustomerRequest $request): RedirectResponse
    {
        $customer = $this->customers->create($request->payload())->record();

        return redirect()
            ->route('customers.show', $customer['id'])
            ->with('success', 'Customer added.');
    }
}
