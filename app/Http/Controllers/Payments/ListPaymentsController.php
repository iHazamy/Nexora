<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\PaymentApi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * GET /payments — money received, business-wide.
 *
 * ! There is NO PAYMENT GATEWAY anywhere in this system. Every row here is an internal
 *   record that someone confirmed money arrived; nothing in Runovia moves funds or talks to
 *   a bank. The screen should not imply otherwise.
 */
class ListPaymentsController extends Controller
{
    public function __construct(private readonly PaymentApi $payments)
    {
    }

    public function __invoke(Request $request): View
    {
        $response = $this->payments->list([
            'invoice_id' => $request->query('invoice_id'),
            'method'     => $request->query('method'),
            'page'       => $request->query('page'),
        ]);

        return view('payments.index', [
            'response' => $response,
            'payments' => $response->records(),
            'methods'  => $this->payments->methodLabels(),
        ]);
    }
}
