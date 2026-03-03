<?php

namespace App\Filament\Widgets;

use App\Models\FinancialRecord;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class FinancialRecordsChart extends ChartWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'Bagan Keuangan';

    protected static ?int $sort = 5;

    public ?string $filter = 'today';

    public function getColumnSpan(): int|string|array
    {
        return [
            'default' => 'full',
            'lg' => 1,
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hari Ini',
            'week' => 'Minggu Ini',
            'month' => 'Bulan Ini',
            'year' => 'Tahun Ini',
        ];
    }

    protected function getData(): array
    {
        $data = match ($this->filter) {
            'today' => Trend::model(FinancialRecord::class)
                ->between(
                    start: now()->startOfDay(),
                    end: now()->endOfDay(),
                )
                ->perHour()
                ->count(),
            'week' => Trend::model(FinancialRecord::class)
                ->between(
                    start: now()->startOfWeek(),
                    end: now()->endOfWeek(),
                )
                ->perDay()
                ->count(),
            'month' => Trend::model(FinancialRecord::class)
                ->between(
                    start: now()->startOfMonth(),
                    end: now()->endOfMonth(),
                )
                ->perDay()
                ->count(),
            'year' => Trend::model(FinancialRecord::class)
                ->between(
                    start: now()->startOfYear(),
                    end: now()->endOfYear(),
                )
                ->perMonth()
                ->count(),
            default => Trend::model(FinancialRecord::class)
                ->between(
                    start: now()->startOfDay(),
                    end: now()->endOfDay(),
                )
                ->perHour()
                ->count(),
        };

        return [
            'datasets' => [
                [
                    'label' => 'Keuangan',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => '#22C55E',
                    'borderColor' => '#F0FDF4',
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Mengubah tipe chart menjadi line untuk visualisasi yang lebih baik
    }

    // Menambahkan konfigurasi chart
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
