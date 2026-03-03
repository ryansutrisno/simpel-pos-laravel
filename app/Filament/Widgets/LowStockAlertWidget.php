<?php

namespace App\Filament\Widgets;

use App\Models\ReorderAlert;
use App\Services\ReorderPointService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class LowStockAlertWidget extends BaseWidget
{
    protected static ?string $heading = 'Peringatan Stok Menipis';

    protected static ?string $description = 'Produk dengan stok di bawah batas minimum';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        // Check stock levels first
        ReorderPointService::checkStockLevels();

        return $table
            ->query(
                ReorderAlert::query()
                    ->with(['product', 'variant'])
                    ->pending()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->description(fn (ReorderAlert $record): ?string => $record->variant?->variant_name),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stok')
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('reorder_point')
                    ->label('Batas Min')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('recommended_order')
                    ->label('Reorder Qty')
                    ->state(function (ReorderAlert $record): int {
                        return ReorderPointService::getRecommendedPurchaseQuantity(
                            $record->product_id,
                            $record->variant_id
                        );
                    })
                    ->badge()
                    ->color('info'),
            ])
            ->actions([
                Tables\Actions\Action::make('acknowledge')
                    ->label('Konfirmasi')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ReorderAlert $record) {
                        ReorderPointService::acknowledgeAlert($record->id, Auth::id(), 'Dikonfirmasi via dashboard');
                    }),

                Tables\Actions\Action::make('view_product')
                    ->label('Lihat Produk')
                    ->icon('heroicon-m-eye')
                    ->url(fn (ReorderAlert $record): string => $record->variant_id
                            ? route('filament.admin.resources.product-variants.edit', $record->variant_id)
                            : route('filament.admin.resources.products.edit', $record->product_id)
                    )
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Tidak ada stok menipis')
            ->emptyStateDescription('Semua stok produk dalam kondisi aman')
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return Auth::user()->can('view_any_product'); // Allow for users who can view products
    }
}
