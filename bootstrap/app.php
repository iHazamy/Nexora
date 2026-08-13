<?php

use App\Http\Middleware\EnsureApiSession;
use App\Http\Middleware\EnsureBusinessExists;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\RedirectIfSignedIn;
use App\Runovia\ApiException;
use App\Runovia\ApiSession;
use App\Runovia\ResponseCode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.session'  => EnsureApiSession::class,
            'api.business' => EnsureBusinessExists::class,
            'guest.only'   => RedirectIfSignedIn::class,
            'platform'     => EnsurePlatformAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
         * ! THE ONE PLACE an API refusal becomes a user-facing response.
         *
         *   The API distinguishes far more failure causes than HTTP statuses do — it
         *   answers HTTP 401 for four different session states and HTTP 403 for six
         *   different refusals — and the right response differs sharply between them.
         *   Handling it here rather than in each controller is what keeps the
         *   controllers in this app three lines long, and means the branch is written
         *   once instead of correctly-in-most-places.
         *
         * ! ORDER MATTERS BELOW. A suspended business (611) must be checked before
         *   anything that redirects to login, because logging in again does not fix
         *   it — the credentials are fine and the next request would be refused
         *   identically. That is an infinite loop with no explanation, and it is the
         *   specific bug this ordering exists to prevent.
         */
        $exceptions->render(function (ApiException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->userMessage()], 502);
            }

            // # A missing RUNOVIA_API_CLIENT_ID is a developer's problem, and saying
            // # "please try again shortly" about it wastes everyone's afternoon.
            if ($e->isConfiguration) {
                return response()->view('errors.runovia-unconfigured', [
                    'detail' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }

            if ($e->isTransport) {
                return response()->view('errors.runovia-unreachable', [
                    'detail' => config('app.debug') ? $e->getMessage() : null,
                ], 503);
            }

            $code = $e->code();

            // # The tenant itself is unavailable. Not a session problem — do not
            // # send them to the login page.
            if ($code->isTenantUnavailable()) {
                return response()->view('errors.tenant-unavailable', [
                    'message'     => $e->userMessage(),
                    'isTemporary' => $code === ResponseCode::MAINTENANCE,
                ], 503);
            }

            if ($code->requiresReauthentication()) {
                app(ApiSession::class)->forget();

                return redirect()->route('login')->with('status', $e->userMessage());
            }

            // # Commercial entitlement, not permission. "Your plan does not include
            // # this" and "you are not allowed" send a paying customer to two very
            // # different places.
            if ($code === ResponseCode::FEATURE_DISABLED) {
                return response()->view('errors.feature-disabled', ['message' => $e->userMessage()], 403);
            }

            if ($code === ResponseCode::PERMISSION_DENIED) {
                return response()->view('errors.permission-denied', ['message' => $e->userMessage()], 403);
            }

            if ($code === ResponseCode::NOT_FOUND) {
                abort(404, $e->userMessage());
            }

            // # Validation and conflicts belong on the form the user just submitted,
            // # with the API's own wording — which is written for users and already
            // # names the field or the offending line.
            if ($code->isInputProblem()) {
                return back()
                    ->withInput()
                    ->withErrors(['runovia' => $e->userMessage()]);
            }

            return response()->view('errors.runovia-failed', [
                'message' => $e->userMessage(),
                'detail'  => config('app.debug') ? $e->getMessage() : null,
            ], 502);
        });
    })->create();
