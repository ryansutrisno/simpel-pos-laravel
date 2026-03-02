<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

class PaymentMethodChartWidget extends ChartWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'Metode Pembayaran';

    protected static ?int $sort = 3;

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

        $payments = Transaction::where('created_at', '>=', $startDate)
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_method')
            ->get();

        $labels = $payments->map(fn ($p) => ucfirst($p->payment_method));
        $data = $payments->map(fn ($p) => $p->count);

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(59, 130, 246)',
                        'rgb(168, 85, 247)',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }

    protected function getColumnSpanResponsive(): array
    {
        return [
            'default' => 12,
            'sm' => 12,
            'md' => 6,
            'xl' => 6,
        ];
    }
}
