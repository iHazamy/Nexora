<?php

declare(strict_types=1);

namespace App\Http\Controllers\Packages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Packages\UpdatePackageRequest;
use App\Runovia\Resources\PackageApi;
use Illuminate\Http\RedirectResponse;

/**
 * PUT /packages/{package}
 *
 * ! Handles TWO submissions, because the route table has no separate deactivate endpoint:
 *   the full edit form, and the one-click Deactivate button that the API's 409 recommends
 *   when a package cannot be deleted. UpdatePackageRequest tells them apart and returns the
 *   right payload for each — critically, the deactivation payload carries neither the other
 *   fields nor an `items` key.
 */
class UpdatePackageController extends Controller
{
    public function __construct(private readonly PackageApi $packages)
    {
    }

    public function __invoke(UpdatePackageRequest $request, int $package): RedirectResponse
    {
        $this->packages->update($package, $request->payload());

        if ($request->isDeactivation()) {
            return back()->with('success', 'Package deactivated. It stays on invoices that already use it.');
        }

        return redirect()
            ->route('packages.show', $package)
            ->with('success', 'Package updated.');
    }
}
