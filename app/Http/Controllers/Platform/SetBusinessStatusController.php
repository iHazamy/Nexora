<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\PlatformApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * PUT /platform/businesses/{business}/status
 *
 * ! THIS IS A SEVERE ACTION. Business status is the API's auth gate 10 and is read LIVE on
 *   every single request, so setting a business to anything but ACTIVE stops every one of its
 *   users on their NEXT request — mid-session, mid-form, with no warning to them. It is not a
 *   flag that takes effect at next login.
 *
 * ! Validated against the API's exact set. A value outside it is a 400, and the confirmation
 *   dialog in the view names both the business and the consequence.
 */
class SetBusinessStatusController extends Controller
{
    public function __construct(private readonly PlatformApi $platform)
    {
    }

    public function __invoke(Request $request, int $business): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:ACTIVE,SUSPENDED,MAINTENANCE,CLOSED'],
        ], [
            'status.in' => 'That is not a status a business can be set to.',
        ]);

        $status = (string) $validated['status'];

        $this->platform->setStatus($business, $status);

        return redirect()
            ->route('platform.dashboard')
            ->with('success', $status === 'ACTIVE'
                ? 'Business reactivated. Its users can sign in again.'
                : "Business set to {$status}. Its users are blocked from their next request onward.");
    }
}
