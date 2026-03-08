<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndOfDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'opening_balance',
        'expected_cash',
        'actual_cash',
        'difference',
        'total_sales',
        'total_cash_sales',
        'total_transfer_sales',
        'total_qris_sales',
        'total_digital_sales',
        'total_invoice_sales',
        'total_multi_sales',
        'total_split_sales',
        'total_transactions',
        'total_profit',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'opening_balance' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'difference' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_cash_sales' => 'decimal:2',
        'total_transfer_sales' => 'decimal:2',
        'total_qris_sales' => 'decimal:2',
        'total_digital_sales' => 'decimal:2',
        'total_invoice_sales' => 'decimal:2',
        'total_multi_sales' => 'decimal:2',
        'total_split_sales' => 'decimal:2',
        'total_profit' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calculateDifference(): void
    {
        $this->difference = $this->actual_cash - $this->expected_cash;
    }

    public function isSurplus(): bool
    {
        return $this->difference > 0;
    }

    public function isDeficit(): bool
    {
        return $this->difference < 0;
    }

    public function isBalanced(): bool
    {
        return $this->difference == 0;
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeToday($query)
    {
        return $query->forDate(now()->toDateString());
    }
}
