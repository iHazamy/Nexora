<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\CustomerApi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * GET /customers
 *
 * ! Filtering, sorting and paging are all done BY THE API, from the query string. They
 *   are not applied to the rows after they arrive: the API paginates, so a client-side
 *   filter would search only the 25 rows currently on screen while appearing to search
 *   everything — a bug that reads as "search is broken" months later.
 */
class ListCustomersController extends Controller
{
    /** ! Whitelisted, and anything else is dropped. The API falls back to its default
     *  sort on an unknown column, so an unfiltered pass-through would silently ignore a
     *  typo instead of showing the user the order they asked for. */
    private const SORTABLE = ['name', 'email', 'created_at', 'updated_at', 'id'];

    public function __construct(private readonly CustomerApi $customers)
    {
    }

    public function __invoke(Request $request): View
    {
        $sort = (string) $request->query('sort', 'name');

        $response = $this->customers->list([
            'search'    => $request->query('search'),
            'sort'      => in_array($sort, self::SORTABLE, true) ? $sort : 'name',
            'direction' => $request->query('direction') === 'desc' ? 'desc' : 'asc',
            'page'      => $request->query('page'),
        ]);

        return view('customers.index', [
            'response'  => $response,
            'customers' => $response->records(),
        ]);
    }
}
