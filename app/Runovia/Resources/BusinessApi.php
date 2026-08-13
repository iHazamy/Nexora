<?php

declare(strict_types=1);

namespace App\Runovia\Resources;

use App\Runovia\ApiResponse;

/**
 * The signed-in user's own business, and the company details printed on invoices.
 *
 * ! There is no index and no {id} route on purpose. A user belongs to exactly one
 *   business and the API resolves it from the token, so there is nothing to
 *   enumerate and no id for this app to pass — or to get wrong.
 */
final class BusinessApi extends ResourceApi
{
    /**
     * @return array<string, mixed>
     */
    public function current(): array
    {
        return $this->httpGet('/api/v1/businesses/current')->record();
    }

    /**
     * Create the business for a user who has none.
     *
     * ! Returns the ApiResponse: creating a second business is a 409, and that
     *   belongs on the form rather than on an error page. It happens legitimately
     *   when someone double-submits or revisits the onboarding URL after finishing.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): ApiResponse
    {
        return $this->call('POST', '/api/v1/businesses', $attributes);
    }

    /**
     * ! Owner only, enforced by the API (this route names OW explicitly and layer 2
     *   gives MG and MB read-only on the business module). Staff can READ the
     *   business because its name and address go on every invoice they raise.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(array $attributes): ApiResponse
    {
        return $this->call('PUT', '/api/v1/businesses/current', $attributes);
    }
}
