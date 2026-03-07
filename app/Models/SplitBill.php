<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SplitBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'split_number',
        'subtotal',
        'payment_method',
        'amount_paid',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'digital' => 'Digital',
            'invoice' => 'Invoice',
            default => ucfirst($this->payment_method),
        };
    }
}
