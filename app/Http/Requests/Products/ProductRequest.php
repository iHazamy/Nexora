<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ! ONE request class for create and update, like CustomerRequest. The API accepts the
 *   same five fields with the same limits either way, so two classes would be one class
 *   and a copy of it.
 *
 * ! IT ALSO CARRIES THE "DEACTIVATE" INTENT, and that needs saying out loud: the route
 *   table has no products.deactivate endpoint, so the one-click Deactivate button posts
 *   to products.update with nothing but a marker. Two user intents therefore arrive at
 *   one endpoint, and the full field rules must NOT be applied to the marker-only
 *   submission — requiring `name` there would refuse the very remedy the API's 409 just
 *   told the user to use.
 *
 * ? Why offer that button at all: a product referenced by a package or an invoice cannot
 *   be deleted, and the API's refusal names deactivating as the fix. Bouncing that
 *   message back to a screen where deactivating is not reachable would be a dead end.
 *
 * ! No pre-check for references anywhere in here. The API owns that rule and enforces it
 *   atomically; a copy of it in this app would be a weaker second opinion.
 */
class ProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->isDeactivation()) {
            // # Nothing else is submitted, so nothing else can be validated.
            return ['deactivate' => ['accepted']];
        }

        return [
            'name'        => ['required', 'string', 'min:2', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type'        => ['required', 'string', 'in:PRODUCT,SERVICE'],

            /*
             * ! `decimal:0,2` and never a float cast. The column is DECIMAL(15,2) and
             *   the value travels as a string end to end — validating the STRING's shape
             *   is what keeps it one. `numeric` here only bounds it; it does not convert
             *   the input, and validated() hands back exactly what was typed.
             *
             * ! Zero is allowed on purpose. A free item on a quote is a real thing, and
             *   the API accepts >= 0.
             */
            'price'       => ['required', 'numeric', 'min:0', 'decimal:0,2'],

            'active'      => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'  => 'Enter a name for this product or service.',
            'type.required'  => 'Choose whether this is a product or a service.',
            'type.in'        => 'Choose whether this is a product or a service.',
            'price.required' => 'Enter a price. Use 0 if it is free.',
            'price.decimal'  => 'A price can have at most two decimal places, for example 85.50.',
            'price.min'      => 'A price cannot be negative.',
        ];
    }

    /**
     * Is this the Deactivate button rather than the form?
     *
     * ! The method check matters. Without it a crafted POST carrying `deactivate=1`
     *   would take the marker-only rule branch and then be handed to create() with an
     *   empty payload.
     */
    public function isDeactivation(): bool
    {
        return $this->isMethod('PUT') && $this->boolean('deactivate');
    }

    /**
     * The payload for the API.
     *
     * ! Sends every key, including the empty ones. The API's PUT leaves an ABSENT key
     *   alone and treats an explicit null as "blank this column", so filtering empties
     *   out here would make a description impossible to ever clear once written.
     *
     * ! `price` goes across as the string that was validated. No number_format, no
     *   (float) — see App\Support\Money for why a DECIMAL(15,2) must not round-trip
     *   through a PHP float, even "just this once".
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        /*
         * ! THE FLAG AND NOTHING ELSE on the deactivate path. The keys below cannot be read
         *   here: rules() validates only `deactivate` on that path, so validated('name'),
         *   validated('type') and validated('price') all resolve to NULL — and the API reads
         *   a key that is PRESENT AND NULL as "blank this column". `name`, `type` and `price`
         *   are NOT NULL, so that is a constraint violation surfacing as a 500, and
         *   `description` would be silently wiped every time someone deactivated a product.
         */
        if ($this->isDeactivation()) {
            return ['active' => false];
        }

        return [
            'name'        => $this->validated('name'),
            'description' => $this->validated('description'),
            'type'        => $this->validated('type'),
            'price'       => $this->validated('price'),

            // # An unchecked box submits nothing, which is why x-form.checkbox emits a
            // # hidden "0" — read here as a real false rather than as "not mentioned".
            'active'      => $this->boolean('active'),
        ];
    }
}
