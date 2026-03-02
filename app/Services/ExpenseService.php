<?php

namespace App\Services;

use App\Models\Expense;
use Carbon\Carbon;

class ExpenseService
{
    public static function generateExpenseNumber(): string
    {
        $prefix = 'EXP';
        $date = now()->format('Ymd');
        $lastExpense = Expense::whereDate('created_at', today())
            ->latest()
            ->first();

        $sequence = $lastExpense
            ? (int) substr($lastExpense->expense_number, -4) + 1
            : 1;

        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }

    public static function getTotalByCategory(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        return Expense::with('category')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->get()
            ->groupBy('expense_category_id')
            ->map(function ($expenses) {
                return [
                    'category' => $expenses->first()->category->name,
                    'total' => $expenses->sum('amount'),
                    'count' => $expenses->count(),
                ];
            })
            ->toArray();
    }

    public static function getDailyExpense(?Carbon $date = null): float
    {
        $date = $date ?? today();

        return (float) Expense::whereDate('expense_date', $date)->sum('amount');
    }

    public static function getMonthlyExpense(?Carbon $date = null): float
    {
        $date = $date ?? now();

        return (float) Expense::whereYear('expense_date', $date->year)
            ->whereMonth('expense_date', $date->month)
            ->sum('amount');
    }

    public static function getExpenseByShift(int $shiftId): float
    {
        return (float) Expense::where('shift_id', $shiftId)->sum('amount');
    }
}
