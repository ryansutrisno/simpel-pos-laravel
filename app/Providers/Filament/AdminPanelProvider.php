<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Backups;
use App\Filament\Widgets\BundleSummaryWidget;
use App\Filament\Widgets\CurrentShiftWidget;
use App\Filament\Widgets\ExpenseSummaryWidget;
use App\Filament\Widgets\FinancialRecordsChart;
use App\Filament\Widgets\LowStockAlertWidget;
use App\Filament\Widgets\PaymentMethodChartWidget;
use App\Filament\Widgets\ProductStatsWidget;
use App\Filament\Widgets\ProfitChartWidget;
use App\Filament\Widgets\SalesChartWidget;
use App\Filament\Widgets\SettingsOverviewWidget;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TopProductsWidget;
use App\Filament\Widgets\TopStaffWidget;
use App\Filament\Widgets\TransactionsChart;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('POS')
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->navigationGroups([
                'Manajemen Produk',
                'Transaksi',
                'Keuangan',
                'Pelanggan',
                'Supplier',
                'Pengaturan',
                'System',
            ])
            ->renderHook(
                'panels::body.end',
                fn (): string => Blade::render("@vite('resources/js/bluetooth-printer.js')")
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\Pos::class,
                Backups::class,
            ])
            // ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // 0. Settings Overview (Pengaturan Aplikasi)
                SettingsOverviewWidget::class,
                // 1. Card Shift - Full width
                CurrentShiftWidget::class,
                // 2. Card Produk Stats (Total Produk, Varian, Paket, Alert)
                ProductStatsWidget::class,
                // 3. Card Total Kategori, Produk, Stok
                StatsOverview::class,
                // 4. Bagan Keuangan & Transaksi (sejajar)
                FinancialRecordsChart::class,
                TransactionsChart::class,
                // 5. Peringatan Stok
                LowStockAlertWidget::class,
                // 6. Tren Profit & Grafik Penjualan (sejajar)
                ProfitChartWidget::class,
                SalesChartWidget::class,
                // 7. Metode Pembayaran & Produk Terlaris (sejajar)
                PaymentMethodChartWidget::class,
                TopProductsWidget::class,
                // 8. Ringkasan Pengeluaran
                ExpenseSummaryWidget::class,
                // 9. Top Performer
                TopStaffWidget::class,
                // 10. Paket Produk
                BundleSummaryWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
