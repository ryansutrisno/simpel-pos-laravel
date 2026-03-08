<?php

namespace App\Services;

use App\Enums\DebtStatus;
use App\Models\EndOfDay;
use App\Models\FinancialRecord;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockHistory;
use App\Models\SupplierDebt;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getSalesReport(Carbon $startDate, Carbon $endDate): array
    {
        $transactions = Transaction::with(['items.product'])
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('created_at')
            ->get();

        $totalSales = $transactions->sum('total');
        $totalTransactions = $transactions->count();
        $totalProfit = $transactions->sum(fn ($t) => $t->items->sum('profit'));

        $salesByPaymentMethod = $transactions->groupBy('payment_method')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ]);

        $salesByDate = $transactions->groupBy(fn ($t) => $t->created_at->toDateString())
            ->map(fn ($group) => [
                'date' => $group->first()->created_at->toDateString(),
                'count' => $group->count(),
                'total' => $group->sum('total'),
                'profit' => $group->sum(fn ($t) => $t->items->sum('profit')),
            ])
            ->values();

        return [
            'transactions' => $transactions,
            'total_sales' => $totalSales,
            'total_transactions' => $totalTransactions,
            'total_profit' => $totalProfit,
            'sales_by_payment_method' => $salesByPaymentMethod,
            'sales_by_date' => $salesByDate,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ];
    }

    public function getProfitLossReport(Carbon $startDate, Carbon $endDate): array
    {
        $records = FinancialRecord::with(['transaction.items'])
            ->whereBetween('record_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('record_date')
            ->get();

        $salesRecords = $records->where('type', 'sales');
        $totalSales = $salesRecords->sum('amount');
        $totalProfit = $salesRecords->sum('profit');

        $expenses = $records->where('type', 'expense');
        $totalExpenses = $expenses->sum('amount');

        $netProfit = $totalProfit - $totalExpenses;

        $dailyBreakdown = $records->groupBy('record_date')
            ->map(function ($dayRecords, $date) {
                $daySales = $dayRecords->where('type', 'sales');
                $dayExpenses = $dayRecords->where('type', 'expense');

                return [
                    'date' => $date,
                    'sales' => $daySales->sum('amount'),
                    'profit' => $daySales->sum('profit'),
                    'expenses' => $dayExpenses->sum('amount'),
                    'net_profit' => $daySales->sum('profit') - $dayExpenses->sum('amount'),
                ];
            })
            ->values();

        return [
            'records' => $records,
            'total_sales' => $totalSales,
            'total_profit' => $totalProfit,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'daily_breakdown' => $dailyBreakdown,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ];
    }

    public function getPurchaseReport(Carbon $startDate, Carbon $endDate, ?int $supplierId = null): array
    {
        $query = PurchaseOrder::with(['supplier', 'items.product'])
            ->whereBetween('order_date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $purchaseOrders = $query->orderBy('order_date')->get();

        $totalAmount = $purchaseOrders->sum('total_amount');
        $totalOrders = $purchaseOrders->count();

        $bySupplier = $purchaseOrders->groupBy('supplier_id')
            ->map(fn ($group) => [
                'supplier' => $group->first()->supplier,
                'count' => $group->count(),
                'total' => $group->sum('total_amount'),
            ])
            ->values();

        $byStatus = $purchaseOrders->groupBy('status')
            ->map(fn ($group) => [
                'status' => $group->first()->status->value,
                'count' => $group->count(),
                'total' => $group->sum('total_amount'),
            ])
            ->values();

        return [
            'purchase_orders' => $purchaseOrders,
            'total_amount' => $totalAmount,
            'total_orders' => $totalOrders,
            'by_supplier' => $bySupplier,
            'by_status' => $byStatus,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ];
    }

    public function getDebtReport(): array
    {
        $debts = SupplierDebt::with(['supplier', 'purchaseOrder', 'payments'])
            ->whereNotIn('status', [DebtStatus::Paid])
            ->orderBy('due_date')
            ->get();

        $totalDebt = $debts->sum('remaining_amount');
        $totalPaid = $debts->sum('paid_amount');

        $overdueDebts = $debts->filter(fn ($debt) => $debt->isOverdue());
        $totalOverdue = $overdueDebts->sum('remaining_amount');

        $agingReport = $debts->groupBy(function ($debt) {
            if ($debt->isOverdue()) {
                $daysOverdue = now()->diffInDays($debt->due_date);
                if ($daysOverdue <= 7) {
                    return '1-7 hari';
                } elseif ($daysOverdue <= 30) {
                    return '8-30 hari';
                } elseif ($daysOverdue <= 60) {
                    return '31-60 hari';
                } else {
                    return '> 60 hari';
                }
            }

            return 'Belum Jatuh Tempo';
        })->map(fn ($group) => [
            'category' => $group->first()->isOverdue() ? 'Overdue' : 'Current',
            'count' => $group->count(),
            'total' => $group->sum('remaining_amount'),
        ]);

        $bySupplier = $debts->groupBy('supplier_id')
            ->map(fn ($group) => [
                'supplier' => $group->first()->supplier,
                'total_debt' => $group->sum('total_amount'),
                'total_paid' => $group->sum('paid_amount'),
                'total_remaining' => $group->sum('remaining_amount'),
                'debts_count' => $group->count(),
            ])
            ->values();

        return [
            'debts' => $debts,
            'total_debt' => $totalDebt,
            'total_paid' => $totalPaid,
            'total_overdue' => $totalOverdue,
            'overdue_count' => $overdueDebts->count(),
            'aging_report' => $agingReport,
            'by_supplier' => $bySupplier,
        ];
    }

    public function getStockCardReport(int $productId, Carbon $startDate, Carbon $endDate): array
    {
        $product = Product::findOrFail($productId);

        $histories = StockHistory::with(['user', 'reference'])
            ->where('product_id', $productId)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('created_at')
            ->get();

        $stockBefore = StockHistory::where('product_id', $productId)
            ->where('created_at', '<', $startDate->startOfDay())
            ->orderBy('created_at', 'desc')
            ->first();

        $openingStock = $stockBefore ? $stockBefore->stock_after : $product->stock;

        $totalIn = $histories->filter(fn ($h) => $h->isIn())->sum('quantity');
        $totalOut = $histories->filter(fn ($h) => $h->isOut())->sum('quantity');
        $closingStock = $product->stock;

        return [
            'product' => $product,
            'histories' => $histories,
            'opening_stock' => $openingStock,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'closing_stock' => $closingStock,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ];
    }

    public function getEndOfDaySummary(Carbon $date): array
    {
        $transactions = Transaction::with(['items'])
            ->whereDate('created_at', $date->toDateString())
            ->get();

        $totalSales = $transactions->sum('total');
        $totalTransactions = $transactions->count();
        $totalProfit = $transactions->sum(fn ($t) => $t->items->sum('profit'));

        // Payment method breakdown
        $cashSales = $transactions->where('payment_method', 'cash');
        $transferSales = $transactions->where('payment_method', 'transfer');
        $qrisSales = $transactions->where('payment_method', 'qris');
        $digitalSales = $transactions->where('payment_method', 'digital');
        $invoiceSales = $transactions->where('payment_method', 'invoice');
        $multiSales = $transactions->where('payment_method', 'multi');
        $splitSales = $transactions->where('payment_method', 'split');

        $previousEndOfDay = EndOfDay::where('date', '<', $date->toDateString())
            ->orderBy('date', 'desc')
            ->first();

        $openingBalance = $previousEndOfDay ? $previousEndOfDay->actual_cash : 0;
        $expectedCash = $openingBalance + $cashSales->sum('total');

        return [
            'date' => $date->toDateString(),
            'opening_balance' => $openingBalance,
            'total_sales' => $totalSales,
            'total_transactions' => $totalTransactions,
            'total_profit' => $totalProfit,
            'total_cash_sales' => $cashSales->sum('total'),
            'total_transfer_sales' => $transferSales->sum('total'),
            'total_qris_sales' => $qrisSales->sum('total'),
            'total_digital_sales' => $digitalSales->sum('total'),
            'total_invoice_sales' => $invoiceSales->sum('total'),
            'total_multi_sales' => $multiSales->sum('total'),
            'total_split_sales' => $splitSales->sum('total'),
            'cash_transactions' => $cashSales->count(),
            'transfer_transactions' => $transferSales->count(),
            'qris_transactions' => $qrisSales->count(),
            'digital_transactions' => $digitalSales->count(),
            'invoice_transactions' => $invoiceSales->count(),
            'multi_transactions' => $multiSales->count(),
            'split_transactions' => $splitSales->count(),
            'expected_cash' => $expectedCash,
            'transactions' => $transactions,
        ];
    }

    public function createEndOfDay(array $data): EndOfDay
    {
        $summary = $this->getEndOfDaySummary(Carbon::parse($data['date']));

        $endOfDay = EndOfDay::create([
            'date' => $data['date'],
            'opening_balance' => $summary['opening_balance'],
            'expected_cash' => $data['expected_cash'] ?? $summary['expected_cash'],
            'actual_cash' => $data['actual_cash'],
            'difference' => $data['actual_cash'] - ($data['expected_cash'] ?? $summary['expected_cash']),
            'total_sales' => $summary['total_sales'],
            'total_cash_sales' => $summary['total_cash_sales'],
            'total_transfer_sales' => $summary['total_transfer_sales'],
            'total_qris_sales' => $summary['total_qris_sales'],
            'total_digital_sales' => $summary['total_digital_sales'],
            'total_invoice_sales' => $summary['total_invoice_sales'],
            'total_multi_sales' => $summary['total_multi_sales'],
            'total_split_sales' => $summary['total_split_sales'],
            'total_transactions' => $summary['total_transactions'],
            'total_profit' => $summary['total_profit'],
            'notes' => $data['notes'] ?? null,
            'user_id' => Auth::id(),
        ]);

        return $endOfDay;
    }

    public function getTopProducts(Carbon $startDate, Carbon $endDate, int $limit = 5): array
    {
        $topProducts = DB::table('transaction_items')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->whereBetween('transactions.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(transaction_items.quantity) as total_quantity'),
                DB::raw('SUM(transaction_items.subtotal) as total_sales'),
                DB::raw('SUM(transaction_items.profit) as total_profit')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();

        return $topProducts->toArray();
    }

    public function getSalesChartData(Carbon $startDate, Carbon $endDate): array
    {
        $salesByDate = Transaction::whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $salesByDate->toArray();
    }

    public function getProfitChartData(Carbon $startDate, Carbon $endDate): array
    {
        $profitByDate = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->whereBetween('transactions.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->select(
                DB::raw('DATE(transactions.created_at) as date'),
                DB::raw('SUM(transaction_items.profit) as total_profit')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $profitByDate->toArray();
    }

    public function getPaymentMethodChartData(Carbon $startDate, Carbon $endDate): array
    {
        $byMethod = Transaction::whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->select('payment_method', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        return $byMethod->toArray();
    }

    public function getReturnReport(Carbon $startDate, Carbon $endDate): array
    {
        $returns = \App\Models\ProductReturn::with(['items.product', 'items.exchangeProduct', 'transaction', 'customer', 'user'])
            ->whereBetween('return_date', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('return_date')
            ->get();

        $totalRefund = $returns->sum('total_refund');
        $totalExchangeValue = $returns->sum('total_exchange_value');
        $totalReturns = $returns->count();

        $byType = $returns->groupBy('type')
            ->map(fn ($group) => [
                'type' => $group->first()->type->value,
                'label' => $group->first()->type->getLabel(),
                'count' => $group->count(),
                'total' => $group->sum('total_refund'),
            ])
            ->values();

        $byReason = $returns->groupBy('reason_category')
            ->map(fn ($group) => [
                'reason' => $group->first()->reason_category?->value,
                'label' => $group->first()->reason_category?->getLabel() ?? 'N/A',
                'count' => $group->count(),
                'total' => $group->sum('total_refund'),
            ])
            ->values();

        $byRefundMethod = $returns->groupBy('refund_method')
            ->map(fn ($group) => [
                'method' => $group->first()->refund_method?->value,
                'label' => $group->first()->refund_method?->getLabel() ?? 'N/A',
                'count' => $group->count(),
                'total' => $group->sum('total_refund'),
            ])
            ->values();

        $byDate = $returns->groupBy(fn ($r) => $r->return_date->toDateString())
            ->map(fn ($group) => [
                'date' => $group->first()->return_date->toDateString(),
                'count' => $group->count(),
                'total_refund' => $group->sum('total_refund'),
                'total_exchange' => $group->sum('total_exchange_value'),
            ])
            ->values();

        $pointsReversed = $returns->sum('points_reversed');
        $pointsReturned = $returns->sum('points_returned');

        return [
            'returns' => $returns,
            'total_refund' => $totalRefund,
            'total_exchange_value' => $totalExchangeValue,
            'total_returns' => $totalReturns,
            'by_type' => $byType,
            'by_reason' => $byReason,
            'by_refund_method' => $byRefundMethod,
            'by_date' => $byDate,
            'points_reversed' => $pointsReversed,
            'points_returned' => $pointsReturned,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ];
    }
}
