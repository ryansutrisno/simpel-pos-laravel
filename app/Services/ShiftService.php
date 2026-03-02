<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ProductReturn;
use App\Models\Shift;
use App\Models\Transaction;
use Carbon\Carbon;

class ShiftService
{
    public static function openShift(int $userId, string $shiftType, float $openingCash): Shift
    {
        // Check if user already has an active shift
        if (self::hasActiveShift($userId)) {
            throw new \Exception('User already has an active shift');
        }

        return Shift::create([
            'user_id' => $userId,
            'shift_type' => $shiftType,
            'shift_date' => today(),
            'opening_cash' => $openingCash,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }

    public static function closeShift(int $shiftId, float $closingCash, ?string $notes = null): Shift
    {
        $shift = Shift::findOrFail($shiftId);

        if ($shift->isClosed()) {
            throw new \Exception('Shift is already closed');
        }

        $expectedCash = self::calculateExpectedCash($shiftId);
        $difference = $closingCash - $expectedCash;

        $shift->update([
            'closing_cash' => $closingCash,
            'expected_cash' => $expectedCash,
            'difference' => $difference,
            'status' => 'closed',
            'closed_at' => now(),
            'notes' => $notes,
        ]);

        return $shift->fresh();
    }

    public static function getActiveShift(int $userId): ?Shift
    {
        return Shift::where('user_id', $userId)
            ->where('status', 'open')
            ->whereDate('shift_date', today())
            ->first();
    }

    public static function hasActiveShift(int $userId): bool
    {
        return self::getActiveShift($userId) !== null;
    }

    public static function canOpenShift(int $userId): bool
    {
        return ! self::hasActiveShift($userId);
    }

    public static function calculateExpectedCash(int $shiftId): float
    {
        $shift = Shift::findOrFail($shiftId);

        // Get all cash transactions during this shift
        $cashSales = Transaction::where('shift_id', $shiftId)
            ->where('payment_method', 'cash')
            ->sum('cash_amount');

        // Get all cash returns during this shift (money going out)
        $cashReturns = ProductReturn::whereHas('transaction', function ($query) use ($shiftId) {
            $query->where('shift_id', $shiftId);
        })
            ->where('refund_method', 'cash')
            ->sum('total_refund');

        // Get all expenses during this shift
        $expenses = Expense::where('shift_id', $shiftId)->sum('amount');

        // Expected cash = opening + cash sales - cash returns - expenses
        return $shift->opening_cash + $cashSales - $cashReturns - $expenses;
    }

    public static function getShiftSummary(int $shiftId): array
    {
        $shift = Shift::findOrFail($shiftId);

        $transactions = Transaction::where('shift_id', $shiftId);
        $expenses = Expense::where('shift_id', $shiftId);

        // Calculate total profit from transaction items
        $transactionIds = Transaction::where('shift_id', $shiftId)->pluck('id');
        $totalProfit = \App\Models\TransactionItem::whereIn('transaction_id', $transactionIds)->sum('profit');

        return [
            'shift' => $shift,
            'total_sales' => (float) $transactions->sum('total'),
            'total_transactions' => $transactions->count(),
            'cash_sales' => (float) $transactions->where('payment_method', 'cash')->sum('total'),
            'transfer_sales' => (float) $transactions->where('payment_method', 'transfer')->sum('total'),
            'qris_sales' => (float) $transactions->where('payment_method', 'qris')->sum('total'),
            'total_expenses' => (float) $expenses->sum('amount'),
            'total_profit' => (float) $totalProfit,
        ];
    }

    public static function getShiftsByDateRange(Carbon $startDate, Carbon $endDate, ?int $userId = null): array
    {
        $query = Shift::whereBetween('shift_date', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->with('user')
            ->orderBy('shift_date', 'desc')
            ->orderBy('opened_at', 'desc')
            ->get()
            ->toArray();
    }

    public static function getCurrentShiftSales(int $shiftId): float
    {
        return (float) Transaction::where('shift_id', $shiftId)->sum('total');
    }

    public static function getCurrentShiftTransactions(int $shiftId): int
    {
        return Transaction::where('shift_id', $shiftId)->count();
    }
}
