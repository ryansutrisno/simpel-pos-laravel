<?php

namespace App\Filament\Widgets;

use App\Models\TransactionItem;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ProfitChartWidget extends ChartWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'Tren Profit';

    protected static ?int $sort = 4;

    public ?string $filter = '7';

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 Hari Terakhir',
            '30' => '30 Hari Terakhir',
        ];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        $profits = TransactionItem::whereHas('transaction', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })
            ->selectRaw('DATE((SELECT created_at FROM transactions WHERE transactions.id = transaction_items.transaction_id)) as date, SUM(profit) as total_profit')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dates = collect();
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $dates->put($date, 0);
        }

        foreach ($profits as $profit) {
            if ($profit->date) {
                $dates->put($profit->date, $profit->total_profit);
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Profit',
                    'data' => $dates->values(),
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'borderColor' => 'rgb(168, 85, 247)',
                    'fill' => true,
                ],
            ],
            'labels' => $dates->keys()->map(fn ($date) => Carbon::parse($date)->format('d/m')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + value.toLocaleString(); }',
                    ],
                ],
            ],
        ];
    }
}
