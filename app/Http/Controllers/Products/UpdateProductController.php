<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductRequest;
use App\Runovia\Resources\ProductApi;
use Illuminate\Http\RedirectResponse;

/**
 * PUT /products/{product}
 *
 * ! TWO USER INTENTS ARRIVE HERE, because the route table has no products.deactivate:
 *   the edit form, and the one-click Deactivate button that the API's delete refusal
 *   tells the user to reach for. The branch below is on WHICH BUTTON was pressed — it is
 *   not a branch on an API status, which is the thing controllers in this app must never
 *   do.
 */
class UpdateProductController extends Controller
{
    public function __construct(private readonly ProductApi $products)
    {
    }

    public function __invoke(ProductRequest $request, int $product): RedirectResponse
    {
        if ($request->isDeactivation()) {
            $this->products->deactivate($product);

            // ! back(), because Deactivate is offered from both the list and the edit
            // ! form, and it is also where the user lands after a 409 from Delete.
            return back()->with(
                'success',
                'Product deactivated. Invoices that already use it are unchanged, '
                . 'but it will not be offered on new ones.'
            );
        }

        $this->products->update($product, $request->payload());

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated.');
    }
}
