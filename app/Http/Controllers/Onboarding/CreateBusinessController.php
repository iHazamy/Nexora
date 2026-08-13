<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Businesses\CreateBusinessRequest;
use App\Runovia\ApiSession;
use App\Runovia\Resources\BusinessApi;
use Illuminate\Http\RedirectResponse;

/**
 * POST /welcome/business — the step that turns a registered user into a usable one.
 *
 * ! This is the ONE authenticated route in the API that works without a business, and
 *   necessarily so: it is how a user who has none gets one, and requiring one would be
 *   circular. Everything else in the app is business-scoped and refused until this
 *   succeeds.
 *
 * ! Creating the business makes the caller its OWNER — a privilege change — and the API
 *   answers with a REPLACEMENT TOKEN carrying the new business and role, having revoked
 *   the one this request was made with. Storing it is mandatory; see the note in
 *   __invoke().
 */
class CreateBusinessController extends Controller
{
    public function __construct(
        private readonly BusinessApi $businesses,
        private readonly ApiSession $session,
    ) {
    }

    public function __invoke(CreateBusinessRequest $request): RedirectResponse
    {
        $response = $this->businesses->create($request->validated());

        if ($response->failed()) {
            /*
             * ! A second business is a 409. It happens legitimately — a double-submit, or
             *   revisiting this URL from browser history after finishing — so rather than
             *   showing an error, treat it as "you already have one" and move them along.
             */
            if ($response->code()->isInputProblem() && $this->session->hasBusiness()) {
                return redirect()->route('dashboard');
            }

            return back()
                ->withInput()
                ->withErrors(['name' => $response->userMessage('We could not create your business.')]);
        }

        /*
         * ! STORE THE TOKEN THE API JUST HANDED BACK. This is not optional and /auth/me is
         *   not a substitute for it.
         *
         *   A user token seals its business id, UserType and grants into the ciphertext at
         *   issue time; nothing re-derives them per request. The token this request was made
         *   with was minted at REGISTRATION and carries no business — and /auth/me reads the
         *   business out of the token, not out of business_users, so asking it would keep
         *   answering `business: null` forever. The user would be returned to this very page
         *   by EnsureBusinessExists, in a loop, having just completed it.
         *
         *   POST /businesses exists in this shape for that reason: it revokes the old token
         *   and returns one that knows about the business. See the API's
         *   AuthService::reissueForMembershipChange() and
         *   tests/Feature/BusinessTokenReissueTest.php.
         */
        $this->session->replaceToken($response->record());

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your business is set up. Add a customer to raise your first invoice.');
    }
}
