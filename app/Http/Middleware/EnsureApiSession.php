<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Runovia\ApiSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuses anything that needs a signed-in user.
 *
 * ! This is a CONVENIENCE, not the security boundary. The API re-authenticates
 *   every single request through its own eleven-gate pipeline and is the only thing
 *   that decides whether a call is allowed. What this buys is that an expired
 *   session becomes a clean redirect to the login page instead of nine failed API
 *   calls rendered as an error screen.
 *
 * ! Consequently it must never be the ONLY thing between a route and data. Every
 *   protected route also carries a real API call whose token the API checks.
 */
class EnsureApiSession
{
    public function __construct(private readonly ApiSession $session)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->session->check()) {
            return $this->reject($request, 'Please sign in to continue.');
        }

        /*
         * ? Checked here as well as by the API (gate 6) because the API's refusal
         *   arrives as a failed call partway through rendering a page, whereas this
         *   produces a redirect before anything has been attempted. The API remains
         *   the authority — this only ever catches the expiry EARLIER, never later.
         */
        if ($this->session->isExpired()) {
            $this->session->forget();

            return $this->reject($request, 'Your session has expired. Please sign in again.');
        }

        return $next($request);
    }

    private function reject(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        /*
         * ! Remembers where they were going, so signing in lands them on the page
         *   they asked for rather than the dashboard. Only for GET: replaying a POST
         *   after a login would re-submit a form the user has forgotten about.
         */
        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('login')->with('status', $message);
    }
}
