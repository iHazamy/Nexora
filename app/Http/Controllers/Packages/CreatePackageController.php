<?php

declare(strict_types=1);

namespace App\Http\Controllers\Packages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Packages\CreatePackageRequest;
use App\Runovia\Resources\PackageApi;
use Illuminate\Http\RedirectResponse;

/**
 * POST /packages
 *
 * ! No try/catch and no status check. PackageApi::create() throws on refusal and the single
 *   handler in bootstrap/app.php decides what each response_code means — a product id from
 *   another business is a 400 that lands back on this form with the API's own message.
 */
class CreatePackageController extends Controller
{
    public function __construct(private readonly PackageApi $packages)
    {
    }

    public function __invoke(CreatePackageRequest $request): RedirectResponse
    {
        $package = $this->packages->create($request->payload())->record();

        return redirect()
            ->route('packages.show', $package['id'])
            ->with('success', 'Package created.');
    }
}
