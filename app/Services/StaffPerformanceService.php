<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StaffPerformanceService
{
    public static function getSalesByUser(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $startDate = $startDate ?? today();
        $endDate = $endDate ?? today();

        return (float) Transaction::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->sum('total');
    }

    public static function getTransactionCountByUser(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        $startDate = $startDate ?? today();
        $endDate = $endDate ?? today();

        return Transaction::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->count();
    }

    public static function getAverageTransactionValue(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $count = self::getTransactionCountByUser($userId, $startDate, $endDate);

        if ($count === 0) {
            return 0;
        }

        $total = self::getSalesByUser($userId, $startDate, $endDate);

        return round($total / $count, 2);
    }

    public static function getItemsSoldByUser(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        $startDate = $startDate ?? today();
        $endDate = $endDate ?? today();

        return Transaction::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->withSum('items', 'quantity')
            ->get()
            ->sum('items_sum_quantity');
    }

    public static function getTopStaff(int $limit = 5, ?Carbon $date = null): Collection
    {
        $date = $date ?? today();

        return User::whereHas('transactions', function ($query) use ($date) {
            $query->whereDate('created_at', $date);
        })
            ->withSum(['transactions as today_sales' => function ($query) use ($date) {
                $query->whereDate('created_at', $date);
            }], 'total')
            ->withCount(['transactions as today_transactions' => function ($query) use ($date) {
                $query->whereDate('created_at', $date);
            }])
            ->orderByDesc('today_sales')
            ->limit($limit)
            ->get();
    }

    public static function compareStaffPerformance(?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $startDate = $startDate ?? today();
        $endDate = $endDate ?? today();

        return User::whereHas('transactions', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
        })
            ->withSum(['transactions as period_sales' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
            }], 'total')
            ->withCount(['transactions as period_transactions' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
            }])
            ->orderByDesc('period_sales')
            ->get()
            ->map(function ($user) {
                return [
                    'user' => $user,
                    'sales' => $user->period_sales ?? 0,
                    'transactions' => $user->period_transactions ?? 0,
                    'average' => $user->period_transactions > 0
                        ? round(($user->period_sales ?? 0) / $user->period_transactions, 2)
                        : 0,
                ];
            });
    }

    public static function getPerformanceTrend(int $userId, int $days = 7): array
    {
        $data = [];
        $endDate = now();
        $startDate = now()->subDays($days - 1);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $sales = self::getSalesByUser($userId, $date->copy(), $date->copy());
            $transactions = self::getTransactionCountByUser($userId, $date->copy(), $date->copy());

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('D'),
                'sales' => $sales,
                'transactions' => $transactions,
            ];
        }

        return $data;
    }
}
