<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Runovia\ApiSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts the platform screens to SA.
 *
 * ! Again a convenience, not the boundary. The API's platform routes name
 *   Policy::platform() and no business role reaches Module::PLATFORM, so a business
 *   user calling them is refused there regardless of what this does. Its purpose is
 *   to make that refusal a redirect instead of an error page.
 */
class EnsurePlatformAdmin
{
    public function __construct(private readonly ApiSession $session)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->session->isPlatformAdmin()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
