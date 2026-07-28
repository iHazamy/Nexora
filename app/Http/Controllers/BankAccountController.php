<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function store(Request $request): RedirectResponse { BankAccount::create($this->validated($request)); return back()->with('success', 'Bank account saved.'); }
    public function update(Request $request, BankAccount $bankAccount): RedirectResponse { $bankAccount->update($this->validated($request)); return back()->with('success', 'Bank account updated.'); }
    public function destroy(BankAccount $bankAccount): RedirectResponse { $bankAccount->delete(); return back()->with('success', 'Bank account deleted.'); }
    private function validated(Request $request): array { return $request->validate(['bank_name' => ['required','string','max:255'], 'account_number' => ['required','string','max:255'], 'account_holder' => ['nullable','string','max:255']]); }
}
