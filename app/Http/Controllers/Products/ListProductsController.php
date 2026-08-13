<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\ProductApi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * GET /products
 *
 * ! Searching, filtering, sorting and paging are all done BY THE API from the query
 *   string, never against the rows after they arrive. The API paginates, so a
 *   client-side filter would search the 25 rows on screen while appearing to search
 *   everything.
 */
class ListProductsController extends Controller
{
    /** ! Whitelisted against the API's own sortable list (models/Product.php). The API
     *  falls back to its default order on an unknown column, so passing the raw value
     *  through would silently ignore a typo instead of showing the user the order they
     *  asked for. */
    private const SORTABLE = ['id', 'name', 'price', 'type', 'created_at', 'updated_at'];

    private const TYPES = ['PRODUCT', 'SERVICE'];

    public function __construct(private readonly ProductApi $products)
    {
    }

    public function __invoke(Request $request): View
    {
        $sort = (string) $request->query('sort', 'name');
        $type = strtoupper((string) $request->query('type', ''));

        /*
         * ! '1' and '0' as STRINGS, and only when one was chosen. ResourceApi::listQuery()
         *   drops null and '' so "any status" sends no filter at all — whereas a cast to
         *   int would turn "any" into 0 and quietly show only the inactive rows.
         */
        $active = match ((string) $request->query('active', '')) {
            '1'     => '1',
            '0'     => '0',
            default => null,
        };

        $response = $this->products->list([
            'search'    => $request->query('search'),
            'type'      => in_array($type, self::TYPES, true) ? $type : null,
            'active'    => $active,
            'sort'      => in_array($sort, self::SORTABLE, true) ? $sort : 'name',
            'direction' => $request->query('direction') === 'desc' ? 'desc' : 'asc',
            'page'      => $request->query('page'),
        ]);

        return view('products.index', [
            'response' => $response,
            'products' => $response->records(),
        ]);
    }
}
