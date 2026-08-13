<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\CustomerApi;
use Illuminate\Contracts\View\View;

/**
 * GET /customers/{customer}/edit
 */
class EditCustomerFormController extends Controller
{
    public function __construct(private readonly CustomerApi $customers)
    {
    }

    public function __invoke(int $customer): View
    {
        return view('customers.form', ['customer' => $this->customers->find($customer)]);
    }
}
