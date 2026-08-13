<?php

declare(strict_types=1);

namespace App\Http\Controllers\BankAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\BankAccounts\BankAccountRequest;
use App\Runovia\Resources\BankAccountApi;
use Illuminate\Http\RedirectResponse;

/**
 * POST /bank-accounts
 *
 * ! Owner only, and the API is what enforces it — the route names OW explicitly and layer 2
 *   gives MG and MB read-only on the business module. Staff can READ an account because it
 *   goes on every invoice they raise; only an owner may change where the money goes.
 *
 * ! A duplicate account number in the same business is a 409, handled centrally. Uniqueness
 *   is per business and not global: two tenants banking with the same bank is unremarkable,
 *   and a global constraint would leak that another tenant holds a given account.
 */
class CreateBankAccountController extends Controller
{
    public function __construct(private readonly BankAccountApi $bankAccounts)
    {
    }

    public function __invoke(BankAccountRequest $request): RedirectResponse
    {
        $this->bankAccounts->create($request->payload());

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Bank account added.');
    }
}
