<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LowStockAlertWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $lowStockCount = Product::whereColumn('stock', '<=', 'low_stock_threshold')
            ->where('is_active', true)
            ->count();

        $outOfStockCount = Product::where('stock', '<=', 0)
            ->where('is_active', true)
            ->count();

        $totalProducts = Product::where('is_active', true)->count();

        return [
            Stat::make('Stok Menipis', $lowStockCount)
                ->description('Produk dengan stok di bawah threshold')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.products.index', [
                    'tableFilters[low_stock][value]' => '1',
                ])),

            Stat::make('Stok Habis', $outOfStockCount)
                ->description('Produk dengan stok habis')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($outOfStockCount > 0 ? 'danger' : 'success')
                ->url(route('filament.admin.resources.products.index', [
                    'tableFilters[out_of_stock][value]' => '1',
                ])),

            Stat::make('Total Produk', $totalProducts)
                ->description('Produk aktif')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),
        ];
    }
}
