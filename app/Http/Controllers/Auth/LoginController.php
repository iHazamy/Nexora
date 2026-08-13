<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Runovia\ApiSession;
use App\Runovia\Resources\AuthApi;
use Illuminate\Http\RedirectResponse;

/**
 * POST /login — exchange credentials for a user token and open a session.
 *
 * ! THE TOKEN-ISSUING ENDPOINT of this app. The API's /auth/login is the only call
 *   that mints a USER token, and this is the only place that token is written to the
 *   session. Nothing else in the app creates one.
 *
 * ! The call itself is made with the APP token, not a user token — there is no user
 *   token yet, and the API still requires the caller to prove it is a registered
 *   client. See RunoviaClient::asApp().
 *
 * ! A platform admin signs in HERE, through this same form. There is no separate admin
 *   login: the API returns role SA because their users row says so, and the redirect
 *   below sends them to the platform screens. What keeps a business user out of those
 *   screens is the permission model, not a second login page.
 */
class LoginController extends Controller
{
    public function __construct(
        private readonly AuthApi $auth,
        private readonly ApiSession $session,
    ) {
    }

    public function __invoke(LoginRequest $request): RedirectResponse
    {
        $response = $this->auth->attemptLogin(
            (string) $request->validated('email'),
            (string) $request->validated('password'),
        );

        if ($response->failed()) {
            /*
             * ! The API's own message, verbatim, and attached to `email` rather than to
             *   `password`. It says only "those credentials do not match" — an unknown
             *   address and a wrong password produce the identical answer — and this app
             *   must not narrow it. Putting it on `password` would imply the address was
             *   recognised.
             *
             * ! withInput() excludes the password. Repopulating a password field means
             *   writing the plaintext into the session and then into the rendered HTML.
             */
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $response->userMessage('Those credentials do not match our records.')]);
        }

        $this->session->start($response->record());

        /*
         * ! One extra call, at sign-in only. /auth/login returns the user, business and
         *   role but NOT the per-user permission grants; /auth/me returns those. Without
         *   it the sidebar and the button hints would fall back to role defaults and
         *   would not reflect an explicitly granted `invoices.D`.
         *
         * ? Cheap here and nowhere else: grants are snapshotted into the token at login
         *   and never change for its lifetime, so there is no reason to re-read them per
         *   request.
         */
        $this->session->refreshIdentity($this->auth->me());

        /*
         * ! The remembered destination is honoured ONLY for a business user who has a
         *   business. For anyone else it is discarded, because it necessarily points at a
         *   page they cannot reach: an SA reaches no business screen at all, and a user
         *   with no business is refused by every business-scoped route the API has.
         *   Following it would land them on a redirect loop or an error, one click after
         *   successfully signing in.
         */
        if ($this->session->isPlatformAdmin()) {
            $request->session()->forget('url.intended');

            return redirect()->route('platform.dashboard');
        }

        if (!$this->session->hasBusiness()) {
            $request->session()->forget('url.intended');

            return redirect()->route('onboarding.business.create');
        }

        return redirect()->intended(route('dashboard'));
    }
}
