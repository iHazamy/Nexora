<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\PaymentApi;
use Illuminate\Http\RedirectResponse;

/**
 * DELETE /payments/{payment}
 *
 * ! REVERSING A PAYMENT REWRITES WHAT A CUSTOMER OWES, which is why the API requires the
 *   D grant that management does not hold by default.
 *
 * ! The invoice's status is re-derived by the API afterwards. An invoice that drops to zero
 *   paid becomes SENT, not DRAFT — it had certainly been issued, and returning it to draft
 *   would suggest it was never sent.
 *
 * ? Redirects back rather than to a fixed page, because this action is offered from two
 *   places — the invoice detail and the payments list — and the user should stay where
 *   they were.
 */
class DeletePaymentController extends Controller
{
    public function __construct(private readonly PaymentApi $payments)
    {
    }

    public function __invoke(int $payment): RedirectResponse
    {
        $this->payments->delete($payment);

        return back()->with('success', 'Payment reversed. The invoice balance has been updated.');
    }
}
