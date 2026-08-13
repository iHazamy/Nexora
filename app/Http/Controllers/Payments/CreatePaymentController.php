<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\CreatePaymentRequest;
use App\Runovia\Resources\PaymentApi;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;

/**
 * POST /invoices/{invoice}/payments
 *
 * ! Nested under the invoice because a payment has no meaning without one — the API models
 *   it the same way, and there is no endpoint to create a free-floating payment.
 *
 * ! Recording a payment CHANGES THE INVOICE'S STATUS. The API re-derives it from the
 *   payment total: SENT becomes PARTIALLY_PAID or PAID. It returns both the payment and
 *   the updated invoice for exactly that reason, and the confirmation below uses the
 *   returned invoice rather than re-reading it or guessing.
 *
 * ! Overpayment is refused by the API with a 400 that names the outstanding balance, and
 *   paying a settled or cancelled invoice is a 409. Both arrive as the API's own message on
 *   this page — no pre-check here, because any pre-check would race a colleague recording a
 *   payment at the same moment.
 */
class CreatePaymentController extends Controller
{
    public function __construct(private readonly PaymentApi $payments)
    {
    }

    public function __invoke(CreatePaymentRequest $request, int $invoice): RedirectResponse
    {
        $response = $this->payments->create($invoice, $request->payload());

        $updated     = $response->get('invoice', []);
        $outstanding = is_array($updated) ? ($updated['outstanding'] ?? null) : null;

        /*
         * ? The message tells the user where the invoice now stands, because that is the
         *   question they were actually answering by recording the payment. Partial payments
         *   are the normal case for an events business taking a deposit, so "paid in full"
         *   versus "still owing X" is the useful distinction.
         */
        $message = $outstanding !== null && Money::isZero($outstanding)
            ? 'Payment recorded. This invoice is now paid in full.'
            : 'Payment recorded.' . ($outstanding !== null
                ? ' Outstanding balance: ' . Money::format($outstanding) . '.'
                : '');

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', $message);
    }
}
