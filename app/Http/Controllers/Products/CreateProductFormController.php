<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * GET /products/create
 */
class CreateProductFormController extends Controller
{
    public function __invoke(): View
    {
        // # One view serves create and edit; a null product is what makes it a create.
        return view('products.form', ['product' => null]);
    }
}
