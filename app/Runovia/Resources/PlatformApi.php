<?php

declare(strict_types=1);

namespace App\Runovia\Resources;

use App\Runovia\ApiResponse;

/**
 * The platform-admin surface: which tenants exist, and whether they may be used.
 *
 * ! SA ONLY, AND AN SA REACHES NOTHING ELSE. Module::forRole() on the API gives the
 *   platform role and the business roles DISJOINT module sets, so a business user
 *   calling these endpoints is refused and an SA calling /customers is refused just
 *   as hard. That mutual exclusion is what replaced a separate `admins` table with
 *   its own token space — it is enforced in one place instead of by two
 *   authentication systems that must each be kept correct.
 *
 * ! THERE IS DELIBERATELY NO METHOD HERE THAT READS A TENANT'S CUSTOMERS, INVOICES
 *   OR PAYMENTS, because there is no endpoint to call. Support access to tenant DATA
 *   is a separate decision with its own PDPA consequences and must not arrive as a
 *   side effect of an admin role that already exists. Nothing in this app should
 *   present that absence as missing functionality.
 *
 * ? The list endpoint is the ONE read in the whole API that spans tenants, which is
 *   why it returns the business record only — never anything a business owns.
 */
final class PlatformApi extends ResourceApi
{
    /**
     * The signed-in platform admin, as the API sees them.
     *
     * ? Read live rather than taken from the session copy. This is the highest-
     *   privilege screen in the app, and the session's role is a cached hint written
     *   at login — asking the API means the identity shown on the page is the one it
     *   would actually act on, and an admin whose account_type was revoked is
     *   refused here instead of being shown a working platform screen.
     *
     * @return array<string, mixed> user, role
     */
    public function me(): array
    {
        return $this->httpGet('/api/v1/admin/me')->record();
    }

    /**
     * Every business on the platform, paged.
     *
     * ! `sort` accepts id, name or created_at and nothing else — those are the only
     *   columns Models\Business declares sortable, and an unknown value falls back to
     *   the default silently rather than erroring, so the caller does the
     *   whitelisting.
     *
     * ? `search` is accepted by the endpoint and then IGNORED: Models\Business
     *   declares no searchable columns. That is why the platform screen offers a
     *   status filter and sortable headings but no search box — a box that quietly
     *   does nothing is worse than no box.
     *
     * @param array<string, mixed> $filters status, sort, direction, page, per_page
     */
    public function listBusinesses(array $filters = []): ApiResponse
    {
        return $this->httpGet('/api/v1/admin/businesses', $this->listQuery($filters));
    }

    /**
     * Set a tenant's availability.
     *
     * ! THIS IS AUTH GATE 10'S INPUT AND IT IS SEVERE. Anything other than ACTIVE
     *   stops every one of that business's users on their NEXT REQUEST — status is
     *   read live on every call, never from their tokens, so nobody keeps working
     *   until a token happens to expire. Whatever they were part-way through, they
     *   are stopped in the middle of.
     *
     * ! An unrecognised status is a 400 with the list of valid ones in its message,
     *   so this throws like every other write and the handler puts that message in
     *   front of the operator. Do not pre-empt it with a second copy of the list —
     *   see statuses(), which exists to build a dropdown, not to validate.
     */
    public function setStatus(int $businessId, string $status): ApiResponse
    {
        return $this->httpPut("/api/v1/admin/businesses/$businessId/status", [
            'status' => $status,
        ]);
    }

    /**
     * The statuses a business can be put into, for a dropdown.
     *
     * ! Hard-coded from Modules\Enum\Business\BusinessStatus, the same way
     *   ActivityLogApi::knownActions() is: the API has no endpoint that enumerates
     *   them. The API re-validates the value it is sent and is the authority — if it
     *   gains a status, an unlisted value still applies correctly when passed, it
     *   just is not offered here.
     *
     * @return array<int, string>
     */
    public function statuses(): array
    {
        return ['ACTIVE', 'SUSPENDED', 'MAINTENANCE', 'CLOSED'];
    }
}
