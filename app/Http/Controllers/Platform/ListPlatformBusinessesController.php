<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\PlatformApi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * GET /platform — every business on the platform.
 *
 * ! THE ONE READ IN THIS ENTIRE API THAT SPANS TENANTS. Everything else is scoped to a
 *   single business_id by the model layer, which throws rather than query without one.
 *
 * ! There is deliberately NO WAY from here to read a tenant's customers, invoices or
 *   payments, and no endpoint that would allow it. Module::forRole() gives SA and the
 *   business roles disjoint module sets, so there is no route to add a check to and no check
 *   to forget. Support access to tenant DATA is a separate decision with its own PDPA
 *   consequences and must not arrive as a side effect of an admin role that already exists.
 *   Do not add a UI that implies otherwise.
 */
class ListPlatformBusinessesController extends Controller
{
    public function __construct(private readonly PlatformApi $platform)
    {
    }

    public function __invoke(Request $request): View
    {
        $response = $this->platform->listBusinesses([
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'page'   => $request->query('page'),
        ]);

        return view('platform.index', [
            'response'   => $response,
            'businesses' => $response->records(),
            'statuses'   => $this->platform->statuses(),
            'admin'      => $this->platform->me(),
        ]);
    }
}
