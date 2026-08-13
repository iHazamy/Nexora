<?php

declare(strict_types=1);

namespace App\Runovia\Resources;

use App\Runovia\ApiResponse;

/**
 * Customers.
 *
 * ! A record belonging to another business answers 404, never 403 — a 403 would
 *   confirm the id exists. So "not found" here genuinely means "not yours or not
 *   real", and this app must not try to distinguish them either.
 */
final class CustomerApi extends ResourceApi
{
    /**
     * @param array<string, mixed> $filters search, sort, direction, page, per_page
     */
    public function list(array $filters = []): ApiResponse
    {
        return $this->httpGet('/api/v1/customers', $this->listQuery($filters));
    }

    /**
     * Every customer, for a picker.
     *
     * ! Asks for the API's maximum page size rather than paginating, because an
     *   invoice form needs one <select> and not an infinite scroll. 100 is
     *   API_MAX_PER_PAGE — asking for more is silently capped, so this is the
     *   largest single call that exists. A business past that will need a
     *   type-ahead rather than a bigger number here.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->httpGet('/api/v1/customers', [
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
        return $this->httpGet("/api/v1/customers/$id")->record();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): ApiResponse
    {
        return $this->httpPost('/api/v1/customers', $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): ApiResponse
    {
        return $this->httpPut("/api/v1/customers/$id", $attributes);
    }

    /**
     * ! A customer with invoices cannot be deleted (409) — it would orphan
     *   financial records. The API's message says so and this app shows it.
     */
    public function delete(int $id): ApiResponse
    {
        return $this->httpDelete("/api/v1/customers/$id");
    }
}
