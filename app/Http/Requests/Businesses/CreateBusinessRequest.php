<?php

declare(strict_types=1);

namespace App\Http\Requests\Businesses;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ! Mirrors the API's own limits (name 200, address 500, and its phone pattern) so a
 *   value that would be refused is caught before the round trip. Where the two could
 *   drift, the API wins — it re-validates everything and its message is what the user
 *   would see anyway.
 *
 * ! Only `name` is required, matching the API. A business signing up should not be
 *   blocked from existing because it has not decided its address yet.
 */
class CreateBusinessRequest extends FormRequest
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter your business name.',
            'phone.regex'   => 'Enter a valid phone number, for example 03-12345678.',
        ];
    }
}
