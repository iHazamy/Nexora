<?php

declare(strict_types=1);

namespace App\Http\Controllers\BankAccounts;

use App\Http\Controllers\Controller;
use App\Runovia\Resources\BankAccountApi;
use Illuminate\Http\RedirectResponse;

/**
 * DELETE /bank-accounts/{bankAccount}
 *
 * ! An account NAMED ON AN INVOICE cannot be deleted — the API answers 409, because that
 *   invoice already told a customer where to pay and removing the row it points at would
 *   leave it unable to answer the question. The refusal returns to the settings page with the
 *   API's own message, and the Deactivate button next to Delete is the remedy.
 */
class DeleteBankAccountController extends Controller
{
    public function __construct(private readonly BankAccountApi $bankAccounts)
    {
    }

    public function __invoke(int $bankAccount): RedirectResponse
    {
        $this->bankAccounts->delete($bankAccount);

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Bank account deleted.');
    }
}
