<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Runovia\ApiSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a signed-in user off the login and register pages.
 *
 * ? Sends a platform admin to their own dashboard rather than the business one,
 *   for the same reason EnsureBusinessExists does: the two role families reach
 *   disjoint parts of the API.
 */
class RedirectIfSignedIn
{
    public function __construct(private readonly ApiSession $session)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->session->check() || $this->session->isExpired()) {
            return $next($request);
        }

        if ($this->session->isPlatformAdmin()) {
            return redirect()->route('platform.dashboard');
        }

        return redirect()->route(
            $this->session->hasBusiness() ? 'dashboard' : 'onboarding.business.create'
        );
    }
}
