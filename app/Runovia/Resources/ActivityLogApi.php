<?php

declare(strict_types=1);

namespace App\Runovia\Resources;

use App\Runovia\ApiResponse;

/**
 * The business's activity log.
 *
 * ! READ ONLY, and there is no write method here because there is no write route.
 *   Entries are a side effect of the action they describe — the API writes exactly
 *   one row per request from Modules\Response — never a direct client write. A
 *   client that could append to an audit trail would make the trail worthless.
 *
 * ? Successful READS produce entries too, with no `action`. "Who looked at the
 *   customer list" is precisely the question an audit trail over other people's
 *   money should answer, so the log is noisier than a change-log would be. That is
 *   the intent, not a defect — hence the `action` filter on the screen.
 *
 * ! `register` and `login` happen before a business exists, so those entries carry
 *   business_id null and never appear here. A business's trail is that business's
 *   history, not the acting user's whole life.
 */
final class ActivityLogApi extends ResourceApi
{
    /**
     * @param array<string, mixed> $filters action, entity_type, user_id, response_code,
     *                                      page, per_page
     */
    public function list(array $filters = []): ApiResponse
    {
        return $this->httpGet('/api/v1/audit-logs', $this->listQuery($filters));
    }

    /**
     * The actions the API records, for the filter dropdown.
     *
     * ! A hard-coded list, and knowingly so. The API has no endpoint that enumerates
     *   them and deriving the options from the current page would offer a filter set
     *   that changes as you page through. Sourced from docs/API.md's Activity Log
     *   section; if the API adds an action, an unlisted value still filters
     *   correctly when passed, it just is not offered here.
     *
     * @return array<int, string>
     */
    public function knownActions(): array
    {
        return [
            'created', 'updated', 'deleted', 'cancelled',
            'register', 'login', 'logout', 'logout_all', 'status_changed',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function knownEntityTypes(): array
    {
        return ['business', 'bank_account', 'customer', 'product', 'package', 'invoice', 'payment', 'user'];
    }
}
