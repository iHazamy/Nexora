<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\ProductApi;
use Illuminate\Contracts\View\View;

/**
 * GET /products/{product}/edit
 *
 * ! Also the detail view for a product — there is no show route. A product carries five
 *   fields, and a read-only page repeating them next to an Edit button would be a screen
 *   whose only purpose is one extra click.
 *
 * ? A product belonging to another business answers 404, not 403: the API will not
 *   confirm that an id exists outside your tenant. That arrives as an ApiException and
 *   becomes a not-found page, so no ownership check belongs here.
 */
class EditProductFormController extends Controller
{
    public function __construct(private readonly ProductApi $products)
    {
    }

    public function __invoke(int $product): View
    {
        return view('products.form', ['product' => $this->products->find($product)]);
    }
}
