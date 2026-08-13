<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoices;

class CreateInvoiceRequest extends InvoiceFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->itemRules() + [
            'customer_id' => ['required', 'integer', 'min:1'],

            // ! Optional. Left blank, the API generates INV-{year}-{0001}, sequential per
            // ! business per year, protected by a unique index. This app must never generate
            // ! one itself.
            'invoice_number' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-\/]+$/'],

            'invoice_date' => ['required', 'date_format:Y-m-d'],

            // ! Mirrors CK_invoices_due_date. Transposing the two dates is the commonest
            // ! date-entry mistake there is, and catching it here names the field.
            'due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:invoice_date'],

            // ? Deliberately unconstrained against invoice_date. An event falls legitimately
            // ? on either side — billing a final balance after the event is normal — and the
            // ? API imposes no constraint either.
            'event_date' => ['nullable', 'date_format:Y-m-d'],

            'attention'       => ['nullable', 'string', 'max:200'],
            'bank_account_id' => ['nullable', 'integer', 'min:1'],

            // ! Only client-settable statuses. PAID and PARTIALLY_PAID are DERIVED from
            // ! recorded payments and assigning either directly is a 400.
            'status' => ['nullable', 'in:DRAFT,SENT,CANCELLED'],

            'discount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'tax'      => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes'    => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * The header. `items` is added by the controller, which always sends it on create.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'customer_id'     => (int) $validated['customer_id'],
            'invoice_number'  => ($validated['invoice_number'] ?? '') ?: null,
            'invoice_date'    => $validated['invoice_date'],
            'due_date'        => ($validated['due_date'] ?? '') ?: null,
            'event_date'      => ($validated['event_date'] ?? '') ?: null,
            'attention'       => ($validated['attention'] ?? '') ?: null,
            'bank_account_id' => ($validated['bank_account_id'] ?? null) ?: null,
            'status'          => ($validated['status'] ?? '') ?: 'DRAFT',
            'discount'        => $this->money($validated['discount'] ?? 0),
            'tax'             => $this->money($validated['tax'] ?? 0),
            'notes'           => ($validated['notes'] ?? '') ?: null,
        ];
    }
}
