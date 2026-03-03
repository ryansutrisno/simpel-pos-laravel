<?php

namespace App\Filament\Widgets;

use App\Models\ProductBundle;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class BundleSummaryWidget extends Widget
{
    protected static string $view = 'filament.widgets.bundle-summary-widget';

    protected static ?string $heading = 'Paket Produk Aktif';

    protected static ?int $sort = 14;

    protected int|string|array $columnSpan = 'full';

    public function getActiveBundlesProperty(): \Illuminate\Support\Collection
    {
        return ProductBundle::active()
            ->valid()
            ->with('items')
            ->orderBy('priority', 'desc')
            ->limit(5)
            ->get();
    }

    public static function canView(): bool
    {
        return Auth::user()->can('view_any_product'); // Allow for users who can view products
    }

    public function getHeading(): string
    {
        return static::$heading ?? 'Paket Produk Aktif';
    }
}
