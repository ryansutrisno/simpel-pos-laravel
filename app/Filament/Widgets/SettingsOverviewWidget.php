<?php

namespace App\Filament\Widgets;

use App\Models\AppSettings;
use App\Models\PaymentGatewayConfig;
use App\Models\PrinterConfig;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SettingsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Pengaturan';

    protected ?string $description = 'Overview Konfigurasi Sistem';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $user = Auth::user();
        $storeId = $user->store_id ?? 1;

        $appSettings = AppSettings::getInstance();
        $appName = $appSettings->app_name ?? 'Simpel POS';
        $isMaintenance = $appSettings->maintenance_mode ?? false;

        $activePrinters = PrinterConfig::forStore($storeId)
            ->active()
            ->count();

        $activePaymentGateways = PaymentGatewayConfig::forStore($storeId)
            ->active()
            ->count();

        $hasDefaultPrinter = PrinterConfig::forStore($storeId)
            ->default()
            ->exists();

        return [
            Stat::make('Nama Aplikasi', $appName)
                ->description($isMaintenance ? 'Mode Maintenance Aktif' : 'Aplikasi Normal')
                ->descriptionIcon($isMaintenance ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($isMaintenance ? 'warning' : 'success')
                ->url('/admin/app-settings/edit', shouldOpenInNewTab: false),

            Stat::make('Printer Aktif', $activePrinters)
                ->description($hasDefaultPrinter ? 'Printer Default: Ada' : 'Belum ada printer default')
                ->descriptionIcon('heroicon-m-printer')
                ->color($activePrinters > 0 ? 'success' : 'danger')
                ->url('/admin/printer-configs', shouldOpenInNewTab: false),

            Stat::make('Payment Gateway', $activePaymentGateways)
                ->description('Provider aktif untuk toko ini')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color($activePaymentGateways > 0 ? 'success' : 'warning')
                ->url('/admin/payment-gateway-configs', shouldOpenInNewTab: false),
        ];
    }
}
