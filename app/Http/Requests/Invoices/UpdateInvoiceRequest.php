<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoices;

/**
 * ! EVERY RULE IS `sometimes`, AND THE PAYLOAD CONTAINS ONLY WHAT WAS SUBMITTED. This is
 *   not laziness about validation — it is the whole reason this class is separate from
 *   CreateInvoiceRequest.
 *
 *   The API applies its financial lock on the mere PRESENCE of a key. Once any payment
 *   exists against an invoice, sending `customer_id`, `discount`, `tax` or `items` is a 409
 *   — even if the value is identical to what is already stored, because the check is
 *   `array_key_exists`, not a comparison. A form that helpfully resubmits every field would
 *   therefore make it impossible to fix a typo in the notes of a part-paid invoice.
 *
 *   So: the edit screen renders the locked fields read-only and does not submit them, and
 *   payload() faithfully forwards only the keys that arrived. Adding a "sensible default"
 *   for a missing key here would silently defeat that.
 */
class UpdateInvoiceRequest extends InvoiceFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->itemRules() + [
            'customer_id'     => ['sometimes', 'required', 'integer', 'min:1'],
            'invoice_number'  => ['sometimes', 'nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-\/]+$/'],
            'invoice_date'    => ['sometimes', 'required', 'date_format:Y-m-d'],
            'due_date'        => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'event_date'      => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'attention'       => ['sometimes', 'nullable', 'string', 'max:200'],
            'bank_account_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'status'          => ['sometimes', 'nullable', 'in:DRAFT,SENT,CANCELLED'],
            'discount'        => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'tax'             => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes'           => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Only the keys the form actually submitted.
     *
     * ! `due_date` is compared against `invoice_date` by the API, which resolves whichever
     *   one is absent from the stored row — so there is deliberately no `after_or_equal`
     *   rule here. This class cannot know the stored invoice_date, and a rule referencing a
     *   field that was not submitted would either never fire or fire wrongly.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $payload   = [];

        // # Nullable text and id fields: an explicit empty submission means "clear this",
        // # which the API expresses as null. An ABSENT key is not touched at all.
        foreach (['invoice_number', 'due_date', 'event_date', 'attention', 'notes'] as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = ($validated[$field] ?? '') !== '' ? $validated[$field] : null;
            }
        }

        if (array_key_exists('bank_account_id', $validated)) {
            $payload['bank_account_id'] = $validated['bank_account_id'] ?: null;
        }

        if (array_key_exists('invoice_date', $validated)) {
            $payload['invoice_date'] = $validated['invoice_date'];
        }

        if (array_key_exists('status', $validated) && ($validated['status'] ?? '') !== '') {
            $payload['status'] = $validated['status'];
        }

        // ! The three financial keys. Included ONLY when submitted, because their presence
        // ! alone is what the API's lock tests for.
        if (array_key_exists('customer_id', $validated)) {
            $payload['customer_id'] = (int) $validated['customer_id'];
        }

        foreach (['discount', 'tax'] as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = $this->money($validated[$field]);
            }
        }

        return $payload;
    }
}
