<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\ProductVariant;
use App\Models\ReorderAlert;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ProductStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $totalVariants = ProductVariant::count();
        $activeBundles = ProductBundle::active()->valid()->count();
        $pendingAlerts = ReorderAlert::pending()->count();

        return [
            Stat::make('Total Produk', $totalProducts)
                ->description('Produk aktif')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary'),

            Stat::make('Total Varian', $totalVariants)
                ->description('Varian produk')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('info'),

            Stat::make('Paket Aktif', $activeBundles)
                ->description('Bundle berlaku')
                ->descriptionIcon('heroicon-m-gift')
                ->color('success'),

            Stat::make('Alert Reorder', $pendingAlerts)
                ->description('Stok menipis')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color($pendingAlerts > 0 ? 'danger' : 'success')
                ->url($pendingAlerts > 0 ? route('filament.admin.pages.manage-reorder-alerts') : null),
        ];
    }

    public static function canView(): bool
    {
        return Auth::user()->can('view_any_product');
    }
}
