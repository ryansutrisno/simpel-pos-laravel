<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Product;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Analisa';

    protected ?string $description = 'Statistik Aplikasi';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Hitung total kategori
        $totalCategories = Category::count();

        // Hitung total produk
        $totalProducts = Product::count();

        // Hitung total stok
        $totalStock = Product::sum('stock');

        return [
            Stat::make('Total Kategori', $totalCategories)
                ->description('Jumlah kategori produk')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->chart([]) // Kosongkan chart karena tidak relevan
                ->color('danger'),

            Stat::make('Total Produk', $totalProducts)
                ->description('Jumlah semua produk')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->chart([]) // Kosongkan chart karena tidak relevan
                ->color('warning'),

            Stat::make('Total Stok', $totalStock)
                ->description('Jumlah stok tersedia')
                ->descriptionIcon('heroicon-m-cube')
                ->chart([]) // Kosongkan chart karena tidak relevan
                ->color('success'),
        ];
    }
}
