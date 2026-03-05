<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPoint;
use App\Models\MembershipTier;

class PointService
{
    public const EARN_RATE = 10000;

    public const REDEEM_RATE = 1000;

    public const MIN_REDEEM = 10;

    public const MAX_REDEEM_PERCENT = 50;

    public function calculateEarnedPoints(float $amount, ?MembershipTier $tier = null): int
    {
        $basePoints = (int) floor($amount / self::EARN_RATE);

        if (! $tier) {
            return $basePoints;
        }

        return (int) floor($basePoints * $tier->multiplier);
    }

    public function calculateRedeemValue(int $points): float
    {
        return $points * self::REDEEM_RATE;
    }

    public function getMaxRedeemablePoints(int $customerPoints, float $transactionTotal): int
    {
        $maxByBalance = $customerPoints;

        $maxByPercent = (int) floor(($transactionTotal * self::MAX_REDEEM_PERCENT / 100) / self::REDEEM_RATE);

        $maxPoints = min($maxByBalance, $maxByPercent);

        return max(0, $maxPoints);
    }

    public function earnPoints(Customer $customer, int $points, ?int $transactionId = null, string $description = ''): CustomerPoint
    {
        return $customer->addPoints($points, $transactionId, $description);
    }

    public function redeemPoints(Customer $customer, int $points, int $transactionId): CustomerPoint
    {
        if ($points < self::MIN_REDEEM) {
            throw new \InvalidArgumentException('Minimum poin untuk ditukar adalah '.self::MIN_REDEEM.' poin');
        }

        if ($points > $customer->getCurrentBalance()) {
            throw new \InvalidArgumentException('Poin tidak mencukupi');
        }

        return $customer->redeemPoints($points, $transactionId);
    }

    public function adjustPoints(Customer $customer, int $points, string $description): CustomerPoint
    {
        $balanceAfter = $customer->points + $points;

        if ($balanceAfter < 0) {
            throw new \InvalidArgumentException('Saldo poin tidak boleh negatif');
        }

        $pointRecord = $customer->pointsHistory()->create([
            'transaction_id' => null,
            'type' => 'adjust',
            'amount' => abs($points),
            'balance_after' => $balanceAfter,
            'description' => $description,
        ]);

        $customer->update(['points' => $balanceAfter]);

        return $pointRecord;
    }

    public function canRedeem(int $customerPoints, float $transactionTotal): bool
    {
        return $customerPoints >= self::MIN_REDEEM && $transactionTotal > 0;
    }
}
