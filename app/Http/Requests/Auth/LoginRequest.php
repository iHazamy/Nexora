<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ! SHAPE ONLY. This checks that an email looks like an email and that a password was
 *   typed — nothing more. Whether the credentials are CORRECT is the API's question,
 *   and it deliberately answers an unknown address and a wrong password with the
 *   identical code and message so the form cannot be used to discover which addresses
 *   are registered. Adding a "no account with that email" check here would hand back
 *   exactly the oracle the API refuses to give.
 *
 * ! No password length rule either. A minimum here would tell an attacker that a
 *   short guess was not even worth sending, and it would reject a legitimate user
 *   whose password predates whatever rule we chose.
 */
class LoginRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email:filter', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required'    => 'Enter your email address.',
            'email.email'       => 'That does not look like an email address.',
            'password.required' => 'Enter your password.',
        ];
    }

    /**
     * ! Lower-cased to match the API, which stores addresses lower-case. Without this
     *   a user who types Sarah@Example.com on a phone keyboard gets a refusal that
     *   looks like a wrong password.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }
}
