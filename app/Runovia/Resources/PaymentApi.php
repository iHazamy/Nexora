<?php

declare(strict_types=1);

namespace App\Runovia\Resources;

use App\Runovia\ApiResponse;

/**
 * Payments — internal records of money received.
 *
 * ! NO PAYMENT GATEWAY. No FPX, Stripe, ToyyibPay or Billplz. Recording a payment
 *   here means someone confirmed money arrived; nothing in this system moves funds
 *   or talks to a bank.
 *
 * ! Creating a payment is nested under its invoice, because a payment has no
 *   meaning without one. Reading is available business-wide as well, for a
 *   "payments received" screen.
 */
final class PaymentApi extends ResourceApi
{
    /**
     * Business-wide.
     *
     * @param array<string, mixed> $filters invoice_id, method, page, per_page
     */
    public function list(array $filters = []): ApiResponse
    {
        return $this->httpGet('/api/v1/payments', $this->listQuery($filters));
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        return $this->httpGet("/api/v1/payments/$id")->record();
    }

    /**
     * One invoice's payments, plus a balance summary.
     *
     * ! Returns `payments` and a `summary` of invoice_total / paid_amount /
     *   outstanding / status — the balance answered directly rather than subtracted
     *   by the caller. Use this instead of summing amounts in PHP: the summary is
     *   computed in integer cents on the API side.
     *
     * @return array<string, mixed>
     */
    public function forInvoice(int $invoiceId): array
    {
        return $this->httpGet("/api/v1/invoices/$invoiceId/payments")->record();
    }

    /**
     * Record money received against an invoice.
     *
     * ! Returns BOTH the payment and the updated invoice, because the invoice's
     *   status changes as a result — SENT becomes PARTIALLY_PAID or PAID. The caller
     *   should use the returned invoice rather than re-reading it.
     *
     * ! Overpayment is REFUSED with a 400 that names the outstanding balance, and
     *   paying a settled or cancelled invoice is a 409. A ledger that can exceed the
     *   invoice produces a negative balance, and there are no credit notes or
     *   refunds in this system to resolve one.
     *
     * @param array<string, mixed> $attributes amount, payment_date, method, reference, notes
     */
    public function create(int $invoiceId, array $attributes): ApiResponse
    {
        return $this->httpPost("/api/v1/invoices/$invoiceId/payments", $attributes);
    }

    /**
     * Reverse a payment.
     *
     * ! Re-derives the invoice's status. An invoice that drops to zero paid becomes
     *   SENT, not DRAFT — it had certainly been issued. Needs the D grant, which
     *   management does not hold by default.
     */
    public function delete(int $id): ApiResponse
    {
        return $this->httpDelete("/api/v1/payments/$id");
    }

    /**
     * @return array<int, string>
     */
    public function methods(): array
    {
        return ['CASH', 'BANK_TRANSFER', 'CHEQUE', 'CARD', 'EWALLET', 'OTHER'];
    }

    /**
     * Human labels for the method codes.
     *
     * @return array<string, string>
     */
    public function methodLabels(): array
    {
        return [
            'CASH'          => 'Cash',
            'BANK_TRANSFER' => 'Bank transfer',
            'CHEQUE'        => 'Cheque',
            'CARD'          => 'Card',
            'EWALLET'       => 'E-wallet',
            'OTHER'         => 'Other',
        ];
    }
}
