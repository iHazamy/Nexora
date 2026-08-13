<?php

declare(strict_types=1);

namespace App\Http\Controllers\Packages;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\PackageApi;
use Illuminate\Http\RedirectResponse;

/**
 * DELETE /packages/{package}
 *
 * ! A package referenced by an invoice CANNOT be deleted — the API answers 409 and its
 *   message names the remedy, which is to deactivate it instead. That refusal is not caught
 *   here: it goes back to the page the user came from with the API's wording, and the
 *   Deactivate button sitting next to Delete is what makes the remedy reachable. Pre-checking
 *   for references would be a second, weaker copy of a rule the API enforces atomically.
 */
class DeletePackageController extends Controller
{
    public function __construct(private readonly PackageApi $packages)
    {
    }

    public function __invoke(int $package): RedirectResponse
    {
        $this->packages->delete($package);

        return redirect()
            ->route('packages.index')
            ->with('success', 'Package deleted.');
    }
}
