<?php

declare(strict_types=1);

namespace App\Http\Controllers\BankAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\BankAccounts\BankAccountRequest;
use App\Runovia\Resources\BankAccountApi;
use Illuminate\Http\RedirectResponse;

/**
 * PUT /bank-accounts/{bankAccount}
 *
 * ! Handles both the edit form and the one-click Deactivate that the API's 409 recommends
 *   when an account cannot be deleted. BankAccountRequest::isDeactivation() tells them apart
 *   and returns a payload of just the flag — sending the other fields on that path would
 *   blank NOT NULL columns.
 *
 * ? Correcting an account number here fixes EVERY invoice that names this account, because
 *   the API reads the bank details through to the invoice rather than copying them onto it.
 *   That is the opposite of the rule for an item's description and price, and deliberately
 *   so: a stale copy of an account number means a customer paying into the wrong account.
 */
class UpdateBankAccountController extends Controller
{
    public function __construct(private readonly BankAccountApi $bankAccounts)
    {
    }

    public function __invoke(BankAccountRequest $request, int $bankAccount): RedirectResponse
    {
        $this->bankAccounts->update($bankAccount, $request->payload());

        return redirect()
            ->route('settings.edit')
            ->with('success', $request->isDeactivation()
                ? 'Bank account deactivated. Invoices that already name it are unchanged.'
                : 'Bank account updated.');
    }
}
