<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductRequest;
use App\Runovia\Resources\ProductApi;
use Illuminate\Http\RedirectResponse;

/**
 * POST /products
 *
 * ! No try/catch and no success check. ProductApi::create() throws on refusal and the
 *   handler in bootstrap/app.php decides what each response_code means — a 400 comes
 *   back to this form with the API's message, a 604 goes to the login page, a 614 to a
 *   "not in your plan" page. Branching here would be that logic written again.
 */
class CreateProductController extends Controller
{
    public function __construct(private readonly ProductApi $products)
    {
    }

    public function __invoke(ProductRequest $request): RedirectResponse
    {
        $this->products->create($request->payload());

        // ! To the index, not to a show page — this resource deliberately has no show
        // ! route, because a product is small enough that its edit form IS the detail
        // ! view.
        return redirect()
            ->route('products.index')
            ->with('success', 'Product added.');
    }
}
