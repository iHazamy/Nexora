<?php

declare(strict_types=1);

namespace App\Http\Requests\Packages;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The rules a package's fields share, for both create and update.
 *
 * ! CREATE AND UPDATE GET SEPARATE SUBCLASSES HERE, unlike products and customers where
 *   one class serves both. The difference is not the validation — it is what the PAYLOAD
 *   is allowed to contain: on update, the mere PRESENCE of the `items` key replaces the
 *   package's contents, so "what to send" genuinely differs between the two operations
 *   and cannot be expressed by one payload() method. The rules live here so they cannot
 *   drift; only the payload is split. See UpdatePackageRequest.
 *
 * ! A package's `price` is its OWN price and is validated on its own. Nothing in this
 *   class compares it to the sum of the items or corrects one to match the other — a
 *   bundle is normally cheaper than its parts, which is the entire point of selling one.
 */
abstract class PackageRequest extends FormRequest
{
    /** Matches PackageRequest::MAX_ITEMS on the API. */
    protected const MAX_ITEMS = 100;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'min:2', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],

            /*
             * ! The package's own price, independent of `items_total`. `decimal:0,2`
             *   validates the SHAPE OF THE STRING rather than casting it — see
             *   App\Support\Money for why a DECIMAL(15,2) never becomes a PHP float here.
             */
            'price'       => ['required', 'numeric', 'min:0', 'decimal:0,2'],

            'active'      => ['boolean'],

            // # The contents. Whether the key reaches the API at all is decided by the
            // # subclass's payload(), not here.
            'items'                => ['array', 'max:' . self::MAX_ITEMS],
            'items.*.product_id'   => ['required', 'integer', 'min:1'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'items.*.unit_price'   => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'                => 'Enter a name for this package.',
            'price.required'               => 'Enter the price you sell this package for.',
            'price.decimal'                => 'A price can have at most two decimal places, for example 850.50.',
            'price.min'                    => 'A price cannot be negative.',
            'items.max'                    => 'A package can hold at most ' . self::MAX_ITEMS . ' items.',
            'items.*.product_id.required'  => 'Choose a product for every line, or remove the line.',
            'items.*.quantity.required'    => 'Enter a quantity for every line.',
            'items.*.quantity.min'         => 'A quantity must be more than zero.',
            'items.*.unit_price.decimal'   => 'A unit price can have at most two decimal places.',
        ];
    }

    /**
     * What to send to the API.
     */
    abstract public function payload(): array;

    /**
     * Did this submission come from a form that MANAGES the item list?
     *
     * ! Answered by a hidden marker, not by whether any `items[]` inputs arrived, and the
     *   distinction is the whole reason the marker exists. A user who removes every row
     *   submits NO items inputs at all — identical on the wire to a form that never
     *   touched the contents. One of those means "empty this package" and the other means
     *   "leave it alone", and the API does exactly what it is told either way.
     *
     * ? Same trick as x-form.checkbox's hidden "0": HTML cannot express "this field was
     *   present but empty", so the form says so explicitly.
     */
    public function managesItems(): bool
    {
        return $this->boolean('items_submitted');
    }

    /**
     * The fields that are not the contents.
     *
     * ! Every key, including the empty ones. The API's PUT leaves an ABSENT key alone and
     *   reads an explicit null as "blank this column", so filtering empties out would
     *   make a description impossible to clear once written.
     *
     * ? Named fields() and not attributes(): FormRequest::attributes() already exists and
     *   means something else entirely — the human names used in validation messages.
     *
     * @return array<string, mixed>
     */
    protected function fields(): array
    {
        return [
            'name'        => $this->validated('name'),
            'description' => $this->validated('description'),
            'price'       => $this->validated('price'),
            'active'      => $this->boolean('active'),
        ];
    }

    /**
     * The item rows as the API wants them.
     *
     * ! `unit_price` is omitted as NULL rather than as '' or 0 when the user left it
     *   blank. The API reads a missing unit_price as "use the product's current price",
     *   which is the intended default; sending 0 would silently make the line free.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function submittedItems(): array
    {
        /** @var array<int, array<string, mixed>> $items */
        $items = $this->validated('items') ?? [];

        // # array_values, because removing a middle row leaves a gap in the submitted
        // # indexes and the API wants a list.
        return array_values(array_map(static function (array $item): array {
            $unitPrice = $item['unit_price'] ?? null;

            return [
                'product_id' => (int) $item['product_id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $unitPrice === '' ? null : $unitPrice,
            ];
        }, $items));
    }
}
