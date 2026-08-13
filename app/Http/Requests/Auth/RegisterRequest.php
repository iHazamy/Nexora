<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * ! The password rule is 8–72 characters, and 72 is not arbitrary: the API hashes
     *   with bcrypt, which TRUNCATES SILENTLY past 72 bytes. A user who set a 90
     *   character passphrase would find that only the first 72 mattered, and that
     *   characters 73 onward could be anything at all on a later sign-in. Rejecting it
     *   here is the honest option.
     *
     * ! `confirmed` is this app's addition, not the API's. The API takes one password
     *   field; a registration form that does not confirm it locks people out of a new
     *   account with a typo they cannot see.
     *
     * ? Uniqueness of the email is NOT checked here — there is no local database to
     *   check against. The API answers a duplicate with a 409, and the controller puts
     *   that message on the form.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:200'],
            'email'    => ['required', 'string', 'email:filter', 'max:255'],
            'phone'    => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s\-()]{5,29}$/'],
            'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'      => 'Enter your name.',
            'email.email'        => 'That does not look like an email address.',
            'phone.regex'        => 'Enter a valid phone number, for example 012-3456789.',
            'password.max'       => 'Passwords are limited to 72 characters.',
            'password.confirmed' => 'The two passwords do not match.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }
}
