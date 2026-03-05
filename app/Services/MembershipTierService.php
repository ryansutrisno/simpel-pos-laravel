<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MembershipTier;
use Illuminate\Support\Collection;

class MembershipTierService
{
    public function getAllTiers(): Collection
    {
        return MembershipTier::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('min_spent')
            ->get();
    }

    public function getDefaultTier(): MembershipTier
    {
        return MembershipTier::where('is_active', true)
            ->orderBy('min_spent')
            ->first();
    }

    public function getTierForSpent(float $totalSpent): ?MembershipTier
    {
        return MembershipTier::where('is_active', true)
            ->where('min_spent', '<=', $totalSpent)
            ->orderBy('min_spent', 'desc')
            ->first();
    }

    public function recalculateCustomerTier(Customer $customer): ?MembershipTier
    {
        $newTier = $this->getTierForSpent((float) $customer->total_spent);

        if ($newTier && $newTier->id !== $customer->membership_tier_id) {
            $customer->update(['membership_tier_id' => $newTier->id]);
        }

        return $newTier;
    }

    public function getNextTier(Customer $customer): ?MembershipTier
    {
        return MembershipTier::where('is_active', true)
            ->where('min_spent', '>', (float) $customer->total_spent)
            ->orderBy('min_spent')
            ->first();
    }

    public function getPointsToNextTier(Customer $customer): ?float
    {
        $nextTier = $this->getNextTier($customer);

        if (! $nextTier) {
            return null;
        }

        return $nextTier->min_spent - (float) $customer->total_spent;
    }

    public function calculatePointsWithTier(float $amount, ?MembershipTier $tier): int
    {
        $basePoints = (int) floor($amount / PointService::EARN_RATE);

        if (! $tier) {
            return $basePoints;
        }

        return (int) floor($basePoints * $tier->multiplier);
    }

    public function assignTierToAllCustomers(): int
    {
        $count = 0;
        $customers = Customer::where('is_active', true)->get();

        foreach ($customers as $customer) {
            $newTier = $this->getTierForSpent((float) $customer->total_spent);
            if ($newTier) {
                $customer->update(['membership_tier_id' => $newTier->id]);
                $count++;
            }
        }

        return $count;
    }
}
