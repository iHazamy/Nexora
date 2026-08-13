<?php

declare(strict_types=1);

namespace App\Http\Requests\BankAccounts;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ! ONE request class for add and edit. The API accepts the same fields with the same
 *   limits either way and its PUT is a partial update rather than a replace, so two
 *   classes would be one class and a copy of it.
 *
 * ! `account_number` IS A STRING, EVERYWHERE. It is validated as text and sent as
 *   text: Malaysian account numbers carry leading zeros, and any integer cast — a PHP
 *   (int), a JSON number, a Blade {{ (int) }} — silently eats them and the invoice then
 *   asks a customer to pay into an account that does not exist. `integer` is not in the
 *   rules below for that reason, and the pattern allows the spaces and dashes people
 *   paste in from a bank statement.
 *
 * ? No uniqueness rule. There is no local database to check, and the API's uniqueness
 *   is PER BUSINESS — two tenants may legitimately share one account number, so a
 *   global check would be wrong even if it were possible. A duplicate within the
 *   business is a 409 that lands back on this form with the API's own wording.
 */
class BankAccountRequest extends FormRequest
{
    /**
     * Mirrors the API's own pattern: starts alphanumeric, then letters, digits, spaces
     * and dashes, 3 to 50 characters.
     */
    private const ACCOUNT_NUMBER_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9\- ]{2,49}$/';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /*
         * ! A deactivation submits no account fields, and asking for the bank name
         *   again in order to switch a flag off would be absurd. It is the remedy the
         *   user is offered after the API refuses a DELETE, so it has to be one click
         *   from the row they just failed to delete — see isDeactivation().
         */
        if ($this->isDeactivation()) {
            return ['intent' => ['required', 'in:deactivate']];
        }

        return [
            'bank_name'      => ['required', 'string', 'max:200'],
            'account_number' => ['required', 'string', 'max:50', 'regex:' . self::ACCOUNT_NUMBER_PATTERN],
            'account_holder' => ['nullable', 'string', 'max:200'],
            'active'         => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bank_name.required'      => 'Enter the bank’s name, for example Maybank.',
            'account_number.required' => 'Enter the account number.',
            'account_number.regex'    => 'Account number must be 3–50 characters, '
                . 'using only letters, digits, spaces and dashes.',
        ];
    }

    /**
     * Is this the "deactivate instead" action rather than an edit?
     *
     * ! Read from the submitted form, not from anything the API said. It marks which of
     *   the two buttons on the row was pressed — the controller is still not allowed to
     *   branch on a RESPONSE.
     */
    public function isDeactivation(): bool
    {
        return $this->input('intent') === 'deactivate';
    }

    /**
     * The payload for the API.
     *
     * ! Every key, including the empty ones, so clearing the account holder actually
     *   clears it — the API's PUT leaves an absent key alone and treats an explicit
     *   null as "blank this column".
     *
     * ! `account_number` is cast to string, never to int. See the class note.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        /*
         * ! THE FLAG AND NOTHING ELSE on the deactivate path. The keys below cannot be read
         *   here: rules() validates only `intent` on that path, so validated('bank_name') and
         *   validated('account_number') resolve to NULL — and the API reads a key that is
         *   PRESENT AND NULL as "blank this column". Both are NOT NULL, so it is a constraint
         *   violation surfacing as a 500, and the account holder would be wiped every time
         *   somebody deactivated an account.
         */
        if ($this->isDeactivation()) {
            return ['active' => false];
        }

        return [
            'bank_name'      => $this->validated('bank_name'),
            'account_number' => (string) $this->validated('account_number'),
            'account_holder' => $this->validated('account_holder'),

            /*
             * ! boolean(), not the raw value. The checkbox component posts a hidden "0"
             *   before the box so an unticked box submits something — without the cast,
             *   the string "0" is truthy and unticking Active would appear to do
             *   nothing.
             */
            'active'         => $this->boolean('active'),
        ];
    }
}
