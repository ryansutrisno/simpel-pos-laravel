<?php

namespace App\Models;

use App\Services\MembershipTierService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'points',
        'store_credit_balance',
        'total_spent',
        'total_transactions',
        'is_active',
        'membership_tier_id',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'store_credit_balance' => 'decimal:2',
            'total_spent' => 'decimal:2',
            'total_transactions' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function membershipTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'membership_tier_id');
    }

    public function pointsHistory(): HasMany
    {
        return $this->hasMany(CustomerPoint::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function storeCredits(): HasMany
    {
        return $this->hasMany(StoreCredit::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ProductReturn::class);
    }

    public function addPoints(int $amount, ?int $transactionId = null, string $description = ''): CustomerPoint
    {
        $balanceAfter = $this->points + $amount;

        $pointRecord = $this->pointsHistory()->create([
            'transaction_id' => $transactionId,
            'type' => 'earn',
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => $description,
        ]);

        $this->update(['points' => $balanceAfter]);

        return $pointRecord;
    }

    public function redeemPoints(int $amount, int $transactionId): CustomerPoint
    {
        $balanceAfter = $this->points - $amount;

        $pointRecord = $this->pointsHistory()->create([
            'transaction_id' => $transactionId,
            'type' => 'redeem',
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => 'Penukaran poin untuk transaksi #'.$transactionId,
        ]);

        $this->update(['points' => $balanceAfter]);

        return $pointRecord;
    }

    public function reversePoints(int $amount, ?int $returnId = null, string $description = ''): CustomerPoint
    {
        $balanceAfter = $this->points - $amount;

        $pointRecord = $this->pointsHistory()->create([
            'transaction_id' => null,
            'type' => 'return_earn',
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => $description,
        ]);

        $this->update(['points' => $balanceAfter]);

        return $pointRecord;
    }

    public function returnPoints(int $amount, ?int $returnId = null, string $description = ''): CustomerPoint
    {
        $balanceAfter = $this->points + $amount;

        $pointRecord = $this->pointsHistory()->create([
            'transaction_id' => null,
            'type' => 'return_redeem',
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => $description,
        ]);

        $this->update(['points' => $balanceAfter]);

        return $pointRecord;
    }

    public function getCurrentBalance(): int
    {
        return $this->points;
    }

    public function getStoreCreditBalance(): float
    {
        return (float) $this->store_credit_balance;
    }

    public function addStoreCredit(float $amount): void
    {
        $this->increment('store_credit_balance', $amount);
    }

    public function useStoreCredit(float $amount): void
    {
        $this->decrement('store_credit_balance', $amount);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function updateStats(float $transactionTotal): void
    {
        $this->increment('total_transactions');
        $this->increment('total_spent', $transactionTotal);
    }

    public function recalculateTier(): ?MembershipTier
    {
        $tierService = app(MembershipTierService::class);
        $newTier = $tierService->getTierForSpent((float) $this->total_spent);

        if ($newTier && $newTier->id !== $this->membership_tier_id) {
            $this->update(['membership_tier_id' => $newTier->id]);
        }

        return $newTier;
    }
}
