<?php

declare(strict_types=1);

namespace App\Http\Controllers\Packages;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\PackageApi;
use App\Runovia\Resources\ProductApi;
use Illuminate\Contracts\View\View;

/**
 * GET /packages/{package}/edit
 */
class EditPackageFormController extends Controller
{
    public function __construct(
        private readonly PackageApi $packages,
        private readonly ProductApi $products,
    ) {
    }

    public function __invoke(int $package): View
    {
        return view('packages.form', [
            // # find() is the only read that returns `items`, which the editor needs.
            'package'  => $this->packages->find($package),
            'products' => $this->products->selectable(),
        ]);
    }
}
