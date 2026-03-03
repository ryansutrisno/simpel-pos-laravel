<?php

namespace App\Filament\Pages;

use App\Models\ReorderAlert;
use App\Services\ReorderPointService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ManageReorderAlerts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Manajemen Produk';

    protected static ?string $navigationLabel = 'Reorder Alert';

    protected static ?string $title = 'Kelola Reorder Alert';

    protected static string $view = 'filament.pages.manage-reorder-alerts';

    public static function getNavigationBadge(): ?string
    {
        return (string) ReorderAlert::pending()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return ReorderAlert::pending()->count() > 0 ? 'danger' : 'success';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ReorderAlert::query()->with(['product', 'variant', 'acknowledgedBy']))
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ReorderAlert $record): ?string => $record->variant?->variant_name),

                TextColumn::make('current_stock')
                    ->label('Stok Saat Ini')
                    ->badge()
                    ->color('danger'),

                TextColumn::make('reorder_point')
                    ->label('Batas Reorder')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'acknowledged' => 'Dikonfirmasi',
                        'ordered' => 'Dipesan',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'danger',
                        'acknowledged' => 'warning',
                        'ordered' => 'success',
                    }),

                TextColumn::make('notified_at')
                    ->label('Diberitahukan')
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('acknowledgedBy.name')
                    ->label('Dikonfirmasi Oleh')
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'acknowledged' => 'Dikonfirmasi',
                        'ordered' => 'Dipesan',
                    ])
                    ->default('pending'),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                \Filament\Tables\Actions\Action::make('acknowledge')
                    ->label('Konfirmasi')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ReorderAlert $record) => $record->isPending())
                    ->action(function (ReorderAlert $record) {
                        ReorderPointService::acknowledgeAlert($record->id, Auth::id(), 'Dikonfirmasi untuk reorder');

                        Notification::make()
                            ->title('Alert dikonfirmasi')
                            ->success()
                            ->send();
                    }),

                \Filament\Tables\Actions\Action::make('order')
                    ->label('Tandai Dipesan')
                    ->icon('heroicon-m-shopping-cart')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (ReorderAlert $record) => $record->isPending() || $record->isAcknowledged())
                    ->action(function (ReorderAlert $record) {
                        ReorderPointService::markAsOrdered($record->id, 'Sudah dipesan dari supplier');

                        Notification::make()
                            ->title('Ditandai sebagai dipesan')
                            ->success()
                            ->send();
                    }),

                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('acknowledge_bulk')
                    ->label('Konfirmasi Terpilih')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Support\Collection $records) {
                        foreach ($records as $record) {
                            if ($record->isPending()) {
                                ReorderPointService::acknowledgeAlert($record->id, Auth::id(), 'Dikonfirmasi secara massal');
                            }
                        }

                        Notification::make()
                            ->title(count($records).' alert dikonfirmasi')
                            ->success()
                            ->send();
                    }),

                BulkAction::make('order_bulk')
                    ->label('Tandai Dipesan')
                    ->icon('heroicon-m-shopping-cart')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Support\Collection $records) {
                        foreach ($records as $record) {
                            if ($record->isPending() || $record->isAcknowledged()) {
                                ReorderPointService::markAsOrdered($record->id, 'Dipesan secara massal');
                            }
                        }

                        Notification::make()
                            ->title(count($records).' alert ditandai dipesan')
                            ->success()
                            ->send();
                    }),

                \Filament\Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('check_stock')
                ->label('Cek Stok Sekarang')
                ->icon('heroicon-m-arrow-path')
                ->action(function () {
                    $alertsCreated = ReorderPointService::checkStockLevels();

                    if (count($alertsCreated) > 0) {
                        Notification::make()
                            ->title(count($alertsCreated).' alert baru dibuat')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Tidak ada alert baru')
                            ->info()
                            ->send();
                    }
                }),
        ];
    }
}
