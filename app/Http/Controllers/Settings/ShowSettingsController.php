<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\BankAccountApi;
use App\Runovia\Resources\BusinessApi;
use Illuminate\Contracts\View\View;

/**
 * GET /settings
 *
 * ! ONE SCREEN FOR THE COMPANY DETAILS AND THE BANK ACCOUNTS, because they are one
 *   idea to the user — "what Runovia prints on my invoices and where the money goes"
 *   — and the API models them the same way: bank accounts live under the `business`
 *   module rather than a module of their own. There is no bank-accounts index route.
 *
 * ! STAFF CAN READ THIS. MG and MB get read-only on the `business` module
 *   deliberately: the company name and address go on every invoice they raise, so
 *   they need to see them. The view renders a definition list instead of a form for
 *   them; it does NOT hide the screen, and it is not what enforces anything — the API
 *   refuses the PUT regardless.
 */
class ShowSettingsController extends Controller
{
    public function __construct(
        private readonly BusinessApi $businesses,
        private readonly BankAccountApi $bankAccounts,
    ) {
    }

    public function __invoke(): View
    {
        /*
         * ! current(), not the copy in the session. The session caches name, email,
         *   phone and address for the shell, but it is a login-time snapshot and does
         *   not carry the registration number, the invoice terms or the logo — and a
         *   settings screen showing a stale name is how an owner concludes their edit
         *   did not save.
         */
        return view('settings.edit', [
            'business'     => $this->businesses->current(),
            'bankAccounts' => $this->bankAccounts->all(),
        ]);
    }
}
