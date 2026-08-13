<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\ProductApi;
use Illuminate\Http\RedirectResponse;

/**
 * DELETE /products/{product}
 *
 * ! A product referenced by a package or an invoice CANNOT be deleted — the API answers
 *   409, and its message names the remedy: deactivate it instead. That refusal is not
 *   handled here. The handler in bootstrap/app.php bounces it back to the page the user
 *   came from with the API's own wording, which is why the list and the edit form both
 *   carry a Deactivate button: the user reads the message and the fix is already on
 *   screen.
 *
 * ! No pre-check for references. It would be a second, weaker copy of a rule the API
 *   enforces inside its own transaction, and it would still lose the race.
 */
class DeleteProductController extends Controller
{
    public function __construct(private readonly ProductApi $products)
    {
    }

    public function __invoke(int $product): RedirectResponse
    {
        $this->products->delete($product);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted.');
    }
}
