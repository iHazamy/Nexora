<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BusinessSettingsRequest;
use App\Runovia\ApiException;
use App\Runovia\ApiSession;
use App\Runovia\Resources\AuthApi;
use App\Runovia\Resources\BusinessApi;
use Illuminate\Http\RedirectResponse;

/**
 * PUT /settings
 *
 * ! OWNER ONLY, and enforced by the API — the route names OW explicitly and layer 2
 *   gives MG and MB read-only on the `business` module. This controller does not check
 *   the role, and must not: the view hides the form as a courtesy, and a staff member
 *   who posts here anyway is refused by the API with a message that says so.
 */
class UpdateSettingsController extends Controller
{
    public function __construct(
        private readonly BusinessApi $businesses,
        private readonly AuthApi $auth,
        private readonly ApiSession $session,
    ) {
    }

    public function __invoke(BusinessSettingsRequest $request): RedirectResponse
    {
        $response = $this->businesses->update($request->payload());

        /*
         * ! THE ONE PLACE IN THIS APP THAT INSPECTS A RESPONSE, and only because
         *   BusinessApi::update() is the one resource method that RETURNS a refusal
         *   instead of throwing it — it calls call() rather than callOrFail(). Its
         *   sibling create() does the same for a documented reason (a second business
         *   is an ordinary 409 on the onboarding form); this one gives none, and the
         *   file is outside this task's scope to change.
         *
         *   Rethrowing hands the refusal to the same handler every other refusal
         *   reaches, so what the user sees is identical to everywhere else: a 400 lands
         *   back on this form with the API's message and their input intact, a staff
         *   member's permission refusal is the permission page, a suspended tenant is
         *   the maintenance page. Without it, a refused save renders "Settings saved."
         *   and silently discards what the owner just typed — which on the record that
         *   prints on every invoice is the worst possible place to be wrong.
         *
         * ? The fix is one word in BusinessApi (call -> callOrFail), after which these
         *   four lines delete.
         */
        if ($response->failed()) {
            throw ApiException::fromResponse($response, 'PUT /api/v1/businesses/current');
        }

        /*
         * ! Re-read the identity so the shell agrees with what was just saved. The
         *   header and the sidebar render the business NAME from the session copy, and
         *   without this an owner who renames their business sees the old name in the
         *   corner of the very page confirming the change.
         *
         * ? /auth/me, not the update response: refreshIdentity() expects a whole
         *   identity payload (user, business, role, permissions) and the update
         *   response carries the business alone.
         */
        $this->session->refreshIdentity($this->auth->me());

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Company details saved.');
    }
}
