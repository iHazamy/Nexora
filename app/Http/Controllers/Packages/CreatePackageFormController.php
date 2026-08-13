<?php

declare(strict_types=1);

namespace App\Http\Controllers\Packages;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\ProductApi;
use Illuminate\Contracts\View\View;

/**
 * GET /packages/create
 */
class CreatePackageFormController extends Controller
{
    public function __construct(private readonly ProductApi $products)
    {
    }

    public function __invoke(): View
    {
        return view('packages.form', [
            'package' => null,

            // ! Active products only. A deactivated product must stay readable on packages
            // ! that already contain it, but offering it for a NEW line is exactly what
            // ! deactivating was meant to stop.
            'products' => $this->products->selectable(),
        ]);
    }
}
