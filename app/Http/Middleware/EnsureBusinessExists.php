<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Runovia\ApiSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a user who has no business yet to create one.
 *
 * ! REGISTERING DOES NOT CREATE A BUSINESS. That is the API's flow, not an
 *   oversight: a user may instead be added to a business that already exists, so
 *   the two steps are separate. Almost every screen in this app is scoped to a
 *   business and the API refuses those routes outright without one, so a
 *   freshly-registered user has to pass through onboarding first.
 *
 * ! Platform admins (SA) are sent elsewhere entirely. Module::forRole() gives SA and
 *   the business roles DISJOINT module sets — an SA reaches no customers, no
 *   invoices and no business — so pushing them into "create your business" would
 *   offer them a step they are specifically excluded from, and the normal dashboard
 *   would be a screen of permission errors.
 */
class EnsureBusinessExists
{
    public function __construct(private readonly ApiSession $session)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->session->isPlatformAdmin()) {
            return redirect()->route('platform.dashboard');
        }

        if (!$this->session->hasBusiness()) {
            return redirect()->route('onboarding.business.create');
        }

        return $next($request);
    }
}
