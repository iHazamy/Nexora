<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ! NO OVERPAYMENT CHECK HERE, deliberately. Whether an amount exceeds what is still
 *   outstanding is the API's question and it refuses with a 400 naming the balance. This
 *   app cannot answer it correctly: it would have to re-read the invoice, subtract two
 *   fixed-2 decimal strings, and race any payment recorded by a colleague in between.
 *   The API decides atomically.
 *
 * ! `method` is validated against the exact set the API accepts, because a value outside
 *   it is a 400 and the form's own select should never be able to produce one.
 */
class CreatePaymentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // ! min:0.01 — a zero payment is not a payment, and the API refuses it.
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'method'       => ['required', 'in:CASH,BANK_TRANSFER,CHEQUE,CARD,EWALLET,OTHER'],
            'reference'    => ['nullable', 'string', 'max:100'],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required'          => 'Enter the amount received.',
            'amount.min'               => 'Enter an amount greater than zero.',
            'payment_date.required'    => 'Enter the date the money was received.',
            'payment_date.date_format' => 'Enter the date as YYYY-MM-DD.',
            'method.required'          => 'Choose how the payment was made.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            // ! Formatted to a fixed-2 string rather than sent as a float, matching how
            // ! the API stores and returns money.
            'amount'       => number_format((float) $this->validated('amount'), 2, '.', ''),
            'payment_date' => $this->validated('payment_date'),
            'method'       => $this->validated('method'),
            'reference'    => $this->validated('reference') ?: null,
            'notes'        => $this->validated('notes') ?: null,
        ];
    }
}
