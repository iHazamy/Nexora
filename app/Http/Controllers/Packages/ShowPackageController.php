<?php

declare(strict_types=1);

namespace App\Http\Controllers\Packages;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\PackageApi;
use Illuminate\Contracts\View\View;

/**
 * GET /packages/{package}
 *
 * ! The single read is the ONLY endpoint that returns a package's `items` and its
 *   `items_total`, which is why this screen exists separately from the index.
 */
class ShowPackageController extends Controller
{
    public function __construct(private readonly PackageApi $packages)
    {
    }

    public function __invoke(int $package): View
    {
        return view('packages.show', ['package' => $this->packages->find($package)]);
    }
}
