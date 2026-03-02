<?php

namespace App\Filament\Widgets;

use App\Services\ExpenseService;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpenseSummaryWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 4;

    protected ?string $heading = 'Ringkasan Pengeluaran';

    protected ?string $description = 'Pengeluaran hari ini dan bulan ini';

    protected function getStats(): array
    {
        $todayExpense = ExpenseService::getDailyExpense();
        $monthlyExpense = ExpenseService::getMonthlyExpense();

        return [
            Stat::make('Pengeluaran Hari Ini', 'Rp '.number_format($todayExpense, 0, ',', '.'))
                ->description('Total pengeluaran hari ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),

            Stat::make('Pengeluaran Bulan Ini', 'Rp '.number_format($monthlyExpense, 0, ',', '.'))
                ->description('Total pengeluaran bulan ini')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->can('view_expense');
    }
}
