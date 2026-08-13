<?php

declare(strict_types=1);

namespace App\Http\Controllers\Packages;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\PackageApi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * GET /packages
 *
 * ! The list endpoint does NOT return a package's items — only the single read does. So
 *   this screen shows the package and its price and links through for the breakdown. It
 *   must not enrich each row with a per-package call: a 25-row page would become 26
 *   requests to display a number nobody asked for.
 */
class ListPackagesController extends Controller
{
    private const SORTABLE = ['name', 'price', 'created_at', 'updated_at', 'id'];

    public function __construct(private readonly PackageApi $packages)
    {
    }

    public function __invoke(Request $request): View
    {
        $sort = (string) $request->query('sort', 'name');

        $response = $this->packages->list([
            'search'    => $request->query('search'),
            'active'    => $request->query('active'),
            'sort'      => in_array($sort, self::SORTABLE, true) ? $sort : 'name',
            'direction' => $request->query('direction') === 'desc' ? 'desc' : 'asc',
            'page'      => $request->query('page'),
        ]);

        return view('packages.index', [
            'response' => $response,
            'packages' => $response->records(),
        ]);
    }
}
