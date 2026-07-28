<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'customer_name', 'attention', 'customer_phone', 'invoice_date', 'event_date', 'due_date', 'bank_account_id', 'subtotal', 'discount', 'deposit', 'grand_total', 'balance', 'paid_at'];
    protected function casts(): array { return ['invoice_date' => 'date', 'event_date' => 'date', 'due_date' => 'date', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'deposit' => 'decimal:2', 'grand_total' => 'decimal:2', 'balance' => 'decimal:2', 'paid_at' => 'datetime']; }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class)->orderBy('sort_order'); }
    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
    public function getStatusAttribute(): string { return (float) $this->balance <= 0 ? 'Paid' : 'Outstanding'; }
}
