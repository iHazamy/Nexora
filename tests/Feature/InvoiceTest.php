<?php

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_new_invoice_form_loads(): void
    {
        $this->get(route('invoices.create'))
            ->assertOk()
            ->assertSee('New Invoice');
    }

    public function test_an_invoice_is_numbered_and_totals_are_calculated(): void
    {
        $response = $this->post(route('invoices.store'), [
            'customer_name' => 'Acme Events',
            'invoice_date' => '2026-07-28',
            'deposit' => 100,
            'items' => [
                ['description' => 'Venue Package A', 'quantity' => 2, 'unit_price' => 250],
                ['description' => 'Catering', 'quantity' => 1, 'unit_price' => 150],
            ],
        ]);

        $invoice = Invoice::firstOrFail();

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame('INV-000001', $invoice->number);
        $this->assertSame('650.00', $invoice->grand_total);
        $this->assertSame('550.00', $invoice->balance);
        $this->assertCount(2, $invoice->items);
    }
}
