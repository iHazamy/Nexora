<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityLog;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\ActivityLogApi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * GET /activity
 *
 * ! READ ONLY, and there is deliberately no write action anywhere in this namespace. Entries
 *   are a side effect of the request they describe — the API writes exactly one row per
 *   response — never a direct client write. A client that could append to an audit trail
 *   over other people's money would make the trail worthless.
 *
 * ! A successful READ produces a row with no `action`. "Who looked at the customer list" is
 *   exactly the question this trail exists to answer, so it is far noisier than a change-log
 *   and users assume it is broken unless told. The view says so, and the `action` filter is
 *   how they narrow to changes.
 */
class ListActivityController extends Controller
{
    public function __construct(private readonly ActivityLogApi $activity)
    {
    }

    public function __invoke(Request $request): View
    {
        $response = $this->activity->list([
            'action'      => $request->query('action'),
            'entity_type' => $request->query('entity_type'),
            'page'        => $request->query('page'),
        ]);

        return view('activity.index', [
            'response'    => $response,
            'entries'     => $response->records(),
            'actions'     => $this->activity->knownActions(),
            'entityTypes' => $this->activity->knownEntityTypes(),
        ]);
    }
}
