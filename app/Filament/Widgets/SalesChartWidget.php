<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesChartWidget extends ChartWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'Grafik Penjualan';

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

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

        $sales = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dates = collect();
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $dates->put($date, 0);
        }

        foreach ($sales as $sale) {
            $dates->put($sale->date, $sale->total);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Penjualan',
                    'data' => $dates->values(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
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
