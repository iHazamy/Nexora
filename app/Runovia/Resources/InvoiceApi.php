<?php

declare(strict_types=1);

namespace App\Runovia\Resources;

use App\Runovia\ApiResponse;

/**
 * Invoices.
 *
 * ! THIS APP NEVER COMPUTES AN INVOICE TOTAL. subtotal, line_total, gross, total,
 *   paid_amount and outstanding are all derived by the API in integer cents and
 *   emitted as fixed-2 strings. This app sends quantity, unit_price, the two
 *   discounts and tax, and renders whatever comes back.
 *
 *   The old standalone app duplicated the same arithmetic in PHP and again in
 *   JavaScript for a live preview, and keeping three copies of a money calculation
 *   agreeing is a losing game. The live preview in this app is explicitly labelled
 *   an estimate and the server's figure is what is ever saved or shown as final.
 *
 * ! Sending `total` has no effect. Only the inputs above are read, which is what
 *   stops a caller invoicing an RM11,350 booking for one ringgit.
 */
final class InvoiceApi extends ResourceApi
{
    /**
     * ! List rows carry `customer_name` and `paid_amount` joined on, so an index
     *   page needs one call and not one per invoice.
     *
     * @param array<string, mixed> $filters search, status, customer_id, sort,
     *                                      direction, page, per_page
     */
    public function list(array $filters = []): ApiResponse
    {
        return $this->httpGet('/api/v1/invoices', $this->listQuery($filters));
    }

    /**
     * One invoice with its items, payments, balance and bank account.
     *
     * ! Everything a printed invoice needs in ONE call — including the joined
     *   customer and bank details. Do not follow it with per-item lookups.
     *
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        return $this->httpGet("/api/v1/invoices/$id")->record();
    }

    /**
     * @param array<string, mixed> $attributes Including a non-empty `items` list.
     */
    public function create(array $attributes): ApiResponse
    {
        return $this->httpPost('/api/v1/invoices', $attributes);
    }

    /**
     * ! Omitting `items` leaves the existing lines alone; sending it replaces them
     *   and recomputes every total. And once ANY payment exists the API locks
     *   `items`, `discount`, `tax` and `customer_id` with a 409 — the outstanding
     *   balance must not move under the customer's feet. `notes`, `due_date`,
     *   `invoice_number`, `event_date`, `attention` and `bank_account_id` still
     *   change, because none of them is a figure.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): ApiResponse
    {
        return $this->httpPut("/api/v1/invoices/$id", $attributes);
    }

    /**
     * Delete a draft, or cancel anything that has been issued.
     *
     * ! ONE endpoint doing two things, and it reports which in `data.action`
     *   ("deleted" or "cancelled"). A DRAFT with no payments never existed as far as
     *   the business is concerned, so it is removed; anything issued or paid is
     *   cancelled, because deleting it would destroy a financial record and leave a
     *   hole in the invoice numbering. The caller must read `action` to tell the
     *   user what actually happened rather than assuming.
     */
    public function deleteOrCancel(int $id): ApiResponse
    {
        return $this->httpDelete("/api/v1/invoices/$id");
    }

    /**
     * Which action deleteOrCancel() took: 'deleted' | 'cancelled'.
     */
    public function actionTaken(ApiResponse $response): string
    {
        $action = $response->get('action');

        return is_string($action) ? $action : 'cancelled';
    }

    /**
     * The statuses a client may set. PAID and PARTIALLY_PAID are derived from
     * payments and assigning either directly is a 400.
     *
     * @return array<int, string>
     */
    public function settableStatuses(): array
    {
        return ['DRAFT', 'SENT', 'CANCELLED'];
    }

    /**
     * Every status an invoice can be in, for a filter dropdown.
     *
     * @return array<int, string>
     */
    public function allStatuses(): array
    {
        return ['DRAFT', 'SENT', 'PARTIALLY_PAID', 'PAID', 'CANCELLED'];
    }
}
