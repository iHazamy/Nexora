<?php

declare(strict_types=1);

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ! ONE request class for both create and update, unlike the controllers which are
 *   split per operation. The rules are genuinely identical — the API accepts the same
 *   fields with the same limits either way, and its PUT is a partial update rather than
 *   a replace — so two classes would be one class and a copy of it, which is how they
 *   drift.
 *
 * ! `name` is the only required field, matching the API. A venue taking a booking over
 *   the phone often has a name and nothing else, and refusing that record would push
 *   them to invent an email address.
 *
 * ? No uniqueness rule on email. There is no local database to check, and the API does
 *   not treat a customer email as unique either — two bookings from the same family
 *   really can share one.
 */
class CustomerRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'min:2', 'max:200'],
            'email'   => ['nullable', 'string', 'email:filter', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s\-()]{5,29}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes'   => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter the customer’s name.',
            'phone.regex'   => 'Enter a valid phone number, for example 012-3456789.',
        ];
    }

    /**
     * The payload for the API.
     *
     * ! Sends every key, including the empty ones, so clearing a field actually clears
     *   it. The API's PUT leaves an ABSENT key alone and treats an explicit null as
     *   "blank this column" — so filtering empties out here would make it impossible to
     *   ever delete a phone number once entered.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'name'    => $this->validated('name'),
            'email'   => $this->validated('email'),
            'phone'   => $this->validated('phone'),
            'address' => $this->validated('address'),
            'notes'   => $this->validated('notes'),
        ];
    }
}
