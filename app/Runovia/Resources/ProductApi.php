<?php

declare(strict_types=1);

namespace App\Runovia\Resources;

use App\Runovia\ApiResponse;

/**
 * Products and services — one resource, discriminated by `type`.
 *
 * ? The old standalone web app had two tables, `packages` and `add_ons`/`services`,
 *   with identical columns. The API models the sellable-thing distinction as a
 *   `type` on one resource instead, which is why there is one class here and not
 *   two: everything that differs between a product and a service is one field.
 *
 * ! No inventory. There are no stock levels anywhere in this system.
 */
final class ProductApi extends ResourceApi
{
    /**
     * @param array<string, mixed> $filters search, type, active, sort, direction, page, per_page
     */
    public function list(array $filters = []): ApiResponse
    {
        return $this->httpGet('/api/v1/products', $this->listQuery($filters));
    }

    /**
     * Active products only, for a picker on the invoice and package forms.
     *
     * ! Filters to active deliberately. A deactivated product must stay readable on
     *   invoices that already reference it, but offering it for a NEW line is
     *   exactly what deactivating was meant to stop.
     *
     * @return array<int, array<string, mixed>>
     */
    public function selectable(): array
    {
        return $this->httpGet('/api/v1/products', [
            'active'    => 1,
            'per_page'  => 100,
            'sort'      => 'name',
            'direction' => 'asc',
        ])->records();
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        return $this->httpGet("/api/v1/products/$id")->record();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): ApiResponse
    {
        return $this->httpPost('/api/v1/products', $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): ApiResponse
    {
        return $this->httpPut("/api/v1/products/$id", $attributes);
    }

    /**
     * ! A product referenced by a package or an invoice cannot be deleted (409).
     *   The remedy the API names is to deactivate it, which keeps it off new quotes
     *   while leaving issued invoices intact — so the UI offers that next to the
     *   error rather than just reporting the refusal.
     */
    public function delete(int $id): ApiResponse
    {
        return $this->httpDelete("/api/v1/products/$id");
    }

    /**
     * Turn a product off without deleting it.
     */
    public function deactivate(int $id): ApiResponse
    {
        return $this->httpPut("/api/v1/products/$id", ['active' => false]);
    }
}
