<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoices;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Shared shape rules for the invoice create and update forms.
 *
 * ! THIS CLASS COMPUTES NO FIGURES. `subtotal`, `line_total`, `total`, `paid_amount` and
 *   `outstanding` are all derived by the API in integer cents, and sending any of them has
 *   no effect. What goes up is `quantity`, `unit_price`, the two discounts, `tax`, and the
 *   item references.
 *
 * ! There are TWO discounts and they are different things:
 *       items[N][discount]  a concession on one LINE, subtracted before the subtotal
 *       discount            a concession on the WHOLE BILL, subtracted after
 *   Neither is derivable from the other and they must not be merged.
 *
 * ! CREATE AND UPDATE ARE SEPARATE SUBCLASSES, and not for tidiness. The API's update
 *   applies its financial lock on the mere PRESENCE of `customer_id`, `discount`, `tax` or
 *   `items` once a payment exists — `array_key_exists`, not a comparison — so an update
 *   that helpfully resubmits an unchanged `customer_id` is refused with a 409. A single
 *   request class marking `customer_id` as required would make it impossible to correct the
 *   notes on a part-paid invoice. See UpdateInvoiceRequest::payload().
 */
abstract class InvoiceFormRequest extends FormRequest
{
    /** Matches the API's own cap; a longer list is refused there anyway. */
    protected const MAX_ITEMS = 200;

    /**
     * The item rules, identical on both paths.
     *
     * @return array<string, mixed>
     */
    protected function itemRules(): array
    {
        return [
            'items'               => ['sometimes', 'array', 'max:' . self::MAX_ITEMS],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.quantity'    => ['required_with:items', 'numeric', 'min:0.01', 'max:999999'],
            'items.*.unit_price'  => ['required_with:items', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.discount'    => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.product_id'  => ['nullable', 'integer', 'min:1'],
            'items.*.package_id'  => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required'     => 'Choose a customer.',
            'invoice_date.required'    => 'Enter the invoice date.',
            'invoice_date.date_format' => 'Enter the invoice date as YYYY-MM-DD.',
            'due_date.after_or_equal'  => 'The due date cannot be before the invoice date.',
            'invoice_number.regex'     => 'Invoice numbers may use letters, digits, dashes and slashes only.',
            'items.*.quantity.min'     => 'Each line needs a quantity of at least 0.01.',
            'items.*.unit_price.min'   => 'A unit price cannot be negative.',
            'items.*.discount.min'     => 'A line discount cannot be negative.',
        ];
    }

    /**
     * Drop rows the user never filled in.
     *
     * ! An invoice editor always shows at least one empty row, so a user who adds three
     *   rows and fills two submits one blank. Passing it through produces a line with no
     *   description and no reference, which the API refuses — giving the user an error for a
     *   row they never meant to create. Stripped BEFORE validation so `items.*.quantity`
     *   never fires on a blank row.
     *
     * ! A row counts as blank only when EVERY meaningful field is empty. A row with a
     *   description and no price is NOT blank — it is a real line the user is part-way
     *   through, and discarding it would lose their work silently.
     */
    protected function prepareForValidation(): void
    {
        if (!is_array($this->input('items'))) {
            return;
        }

        $items = array_values(array_filter(
            $this->input('items'),
            static function (mixed $row): bool {
                if (!is_array($row)) {
                    return false;
                }

                $filled = array_filter([
                    trim((string) ($row['description'] ?? '')),
                    trim((string) ($row['product_id'] ?? '')),
                    trim((string) ($row['package_id'] ?? '')),
                ]);

                if ($filled !== []) {
                    return true;
                }

                $price = trim((string) ($row['unit_price'] ?? ''));

                return $price !== '' && (float) $price > 0;
            }
        ));

        $this->merge(['items' => $items]);
    }

    /**
     * Cross-field item rules the rule array cannot express.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $items = $this->input('items');

                if (!is_array($items)) {
                    return;
                }

                foreach ($items as $index => $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    $productId = trim((string) ($item['product_id'] ?? ''));
                    $packageId = trim((string) ($item['package_id'] ?? ''));

                    // ! Product OR package OR neither, never both — two references leave the
                    // ! line's price basis ambiguous, and a CHECK constraint refuses it.
                    if ($productId !== '' && $packageId !== '') {
                        $validator->errors()->add(
                            "items.$index.product_id",
                            'A line can reference either a product or a package, not both.'
                        );
                    }

                    // ! A free-text line must describe itself. A referenced line may omit the
                    // ! description: the API copies the product or package name in at issue
                    // ! time, so a later rename does not rewrite an invoice already sent.
                    if ($productId === '' && $packageId === ''
                        && trim((string) ($item['description'] ?? '')) === '') {
                        $validator->errors()->add(
                            "items.$index.description",
                            'Choose an item or type a description for this line.'
                        );
                    }
                }
            },
        ];
    }

    /**
     * The submitted lines, normalised for the API, or null when none were submitted.
     *
     * ! null and [] MEAN DIFFERENT THINGS to the API. A missing `items` key leaves the
     *   existing lines untouched; `[]` would replace them with nothing. Returning null is
     *   what lets a locked invoice's notes be edited without touching its lines.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function items(): ?array
    {
        if (!$this->has('items')) {
            return null;
        }

        $items = $this->validated()['items'] ?? null;

        if (!is_array($items)) {
            return null;
        }

        return array_values(array_map(static fn (array $item): array => [
            'description' => trim((string) ($item['description'] ?? '')) ?: null,
            'quantity'    => number_format((float) ($item['quantity'] ?? 1), 2, '.', ''),
            'unit_price'  => number_format((float) ($item['unit_price'] ?? 0), 2, '.', ''),
            'discount'    => number_format((float) ($item['discount'] ?? 0), 2, '.', ''),
            'product_id'  => ($item['product_id'] ?? null) ? (int) $item['product_id'] : null,
            'package_id'  => ($item['package_id'] ?? null) ? (int) $item['package_id'] : null,
        ], $items));
    }

    /**
     * A money input as the fixed-2 string the API expects.
     *
     * ! Formatted, not cast. Binding a raw float to DECIMAL(15,2) is the conversion the
     *   API's string encoding exists to prevent.
     */
    protected function money(mixed $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
