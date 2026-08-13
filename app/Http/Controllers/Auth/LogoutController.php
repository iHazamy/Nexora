<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Runovia\ApiException;
use App\Runovia\ApiSession;
use App\Runovia\Resources\AuthApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * POST /logout
 *
 * ! Revokes the calling token only. Signing out on a laptop leaves a phone signed in,
 *   which is what a user expects and what the API's default does.
 */
class LogoutController extends Controller
{
    public function __construct(
        private readonly AuthApi $auth,
        private readonly ApiSession $session,
    ) {
    }

    public function __invoke(): RedirectResponse
    {
        /*
         * ! THE ONE PLACE an API failure is deliberately swallowed.
         *
         *   The local session is cleared either way, so the user is signed out of this
         *   app regardless. If the revoke call fails — the token was already revoked, the
         *   API is briefly unreachable — showing an error page on the way out is the
         *   least useful moment for one, and the user's intent has been honoured as far
         *   as this app can honour it.
         *
         * ! It is logged rather than ignored, because a token that could not be revoked
         *   stays valid until it expires, and that is worth knowing.
         */
        try {
            $response = $this->auth->logout();

            if ($response->failed()) {
                Log::info('Runovia token revoke refused during sign-out', [
                    'response_code' => $response->responseCode,
                ]);
            }
        } catch (ApiException $e) {
            Log::warning('Runovia token revoke failed during sign-out', ['error' => $e->getMessage()]);
        }

        // # Invalidates the session and regenerates the CSRF token, so nothing from this
        // # visit survives into the next one.
        $this->session->forget();

        return redirect()->route('login')->with('status', 'You have been signed out.');
    }
}
