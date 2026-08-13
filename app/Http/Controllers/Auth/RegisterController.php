<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Runovia\ApiSession;
use App\Runovia\Resources\AuthApi;
use Illuminate\Http\RedirectResponse;

/**
 * POST /register
 *
 * ! REGISTERING CREATES A USER AND NOTHING ELSE — no business. That is the API's flow
 *   rather than an omission here: a person may instead be invited into a business that
 *   already exists, so the two steps are separate. The redirect below therefore goes to
 *   onboarding, and almost nothing else in the app is reachable until that is done.
 *
 * ! `account_type` is not writable through any API endpoint, so nothing in this request
 *   body can mint a platform admin. That guarantee is the API's; this controller does
 *   not need to filter for it, and should not pretend to.
 */
class RegisterController extends Controller
{
    public function __construct(
        private readonly AuthApi $auth,
        private readonly ApiSession $session,
    ) {
    }

    public function __invoke(RegisterRequest $request): RedirectResponse
    {
        $response = $this->auth->register([
            'name'     => $request->validated('name'),
            'email'    => $request->validated('email'),
            'phone'    => $request->validated('phone'),
            'password' => $request->validated('password'),
        ]);

        if ($response->failed()) {
            /*
             * ! A duplicate address is the expected failure and the API answers it with a
             *   409 whose message says so, so it belongs on the email field.
             *
             * ? Unlike the login form, being specific here is safe and necessary: the
             *   person is telling us they own this address, and "that email is already
             *   registered" is the only message that lets them go and sign in instead.
             *   Registration inherently discloses whether an address is taken.
             */
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => $response->userMessage('We could not create that account.')]);
        }

        /*
         * ! The register response carries a token, so the user is signed in immediately
         *   rather than being bounced to the login form to retype what they just typed.
         *
         * ! It does NOT carry `business` or `role` — there is no business yet — so
         *   ApiSession::start() records those as null, and EnsureBusinessExists sends
         *   them to onboarding on the next request. That is the intended path, not a gap.
         */
        $this->session->start($response->record());

        return redirect()
            ->route('onboarding.business.create')
            ->with('status', 'Your account is ready. One more step — tell us about your business.');
    }
}
