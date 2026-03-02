<?php

namespace App\Filament\Widgets;

use App\Models\TransactionItem;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

class TopProductsWidget extends ChartWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'Produk Terlaris';

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

        $topProducts = TransactionItem::whereHas('transaction', function ($query) use ($startDate) {
            $query->where('created_at', '>=', $startDate);
        })
            ->with('product')
            ->selectRaw('product_id, SUM(quantity) as total_qty')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Qty Terjual',
                    'data' => $topProducts->pluck('total_qty'),
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                    ],
                ],
            ],
            'labels' => $topProducts->map(fn ($item) => $item->product?->name ?? 'Unknown'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
