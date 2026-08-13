<?php

declare(strict_types=1);

namespace App\Runovia\Resources;

use App\Runovia\ApiResponse;

/**
 * Packages — a bundle of products sold as one line.
 *
 * ! A package's `price` is its OWN price and is independent of the sum of its
 *   items. A bundle is normally cheaper than its parts, which is the entire point
 *   of selling one. The API returns both `price` and `items_total` so a form can
 *   show the difference; this app must never "correct" one to match the other.
 */
final class PackageApi extends ResourceApi
{
    /**
     * ! The list does NOT include items — only the single read does. A screen that
     *   needs the contents of every package has to ask per package, which is why the
     *   index page shows a count and the detail page shows the breakdown.
     *
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = []): ApiResponse
    {
        return $this->httpGet('/api/v1/packages', $this->listQuery($filters));
    }

    /**
     * Active packages for a picker on the invoice form.
     *
     * @return array<int, array<string, mixed>>
     */
    public function selectable(): array
    {
        return $this->httpGet('/api/v1/packages', [
            'active'    => 1,
            'per_page'  => 100,
            'sort'      => 'name',
            'direction' => 'asc',
        ])->records();
    }

    /**
     * One package, with its `items`.
     *
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        return $this->httpGet("/api/v1/packages/$id")->record();
    }

    /**
     * @param array<string, mixed> $attributes Including an `items` list.
     */
    public function create(array $attributes): ApiResponse
    {
        return $this->httpPost('/api/v1/packages', $attributes);
    }

    /**
     * ! On update, sending `items` REPLACES the contents wholesale. Omitting the key
     *   leaves them alone; sending [] empties them. So a form that edits only the
     *   name must not send an empty items array, or it silently guts the package —
     *   see UpdatePackageRequest, which only includes the key when items were part
     *   of the submission.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): ApiResponse
    {
        return $this->httpPut("/api/v1/packages/$id", $attributes);
    }

    /**
     * ! Refused with 409 if an invoice references the package. Deactivate instead.
     */
    public function delete(int $id): ApiResponse
    {
        return $this->httpDelete("/api/v1/packages/$id");
    }

    public function deactivate(int $id): ApiResponse
    {
        return $this->httpPut("/api/v1/packages/$id", ['active' => false]);
    }
}
