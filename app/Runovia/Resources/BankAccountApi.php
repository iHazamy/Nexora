<?php

declare(strict_types=1);

namespace App\Runovia\Resources;

use App\Runovia\ApiResponse;

/**
 * The business's bank accounts — where it asks to be paid.
 *
 * ! Read by anyone, written by the owner alone. These live under the API's
 *   `business` module, which already draws that line: staff need the account to put
 *   it on an invoice they raise, and must not be able to change where the money
 *   goes.
 *
 * ! `account_number` is a STRING everywhere in this app. Malaysian account numbers
 *   carry leading zeros, and any integer cast — a PHP (int), a JSON number, a
 *   Blade {{ (int) }} — silently destroys them.
 */
final class BankAccountApi extends ResourceApi
{
    /**
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = []): ApiResponse
    {
        return $this->httpGet('/api/v1/bank-accounts', $this->listQuery($filters));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->httpGet('/api/v1/bank-accounts', [
            'per_page'  => 100,
            'sort'      => 'bank_name',
            'direction' => 'asc',
        ])->records();
    }

    /**
     * Active accounts, for the invoice form's picker.
     *
     * ! Only active ones are offered for a NEW invoice, but an invoice that already
     *   names a deactivated account still saves — the API allows it deliberately, so
     *   that deactivating cannot strand an invoice mid-edit.
     *
     * @return array<int, array<string, mixed>>
     */
    public function selectable(): array
    {
        return $this->httpGet('/api/v1/bank-accounts', [
            'active'    => 1,
            'per_page'  => 100,
            'sort'      => 'bank_name',
            'direction' => 'asc',
        ])->records();
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        return $this->httpGet("/api/v1/bank-accounts/$id")->record();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): ApiResponse
    {
        return $this->httpPost('/api/v1/bank-accounts', $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): ApiResponse
    {
        return $this->httpPut("/api/v1/bank-accounts/$id", $attributes);
    }

    /**
     * ! Refused with 409 when an invoice names this account, because that invoice
     *   already told a customer where to pay. Deactivate instead.
     */
    public function delete(int $id): ApiResponse
    {
        return $this->httpDelete("/api/v1/bank-accounts/$id");
    }

    public function deactivate(int $id): ApiResponse
    {
        return $this->httpPut("/api/v1/bank-accounts/$id", ['active' => false]);
    }
}
