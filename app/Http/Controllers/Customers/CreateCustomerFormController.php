<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * GET /customers/create
 */
class CreateCustomerFormController extends Controller
{
    public function __invoke(): View
    {
        // # One view serves create and edit; a null customer is what makes it a create.
        return view('customers.form', ['customer' => null]);
    }
}
