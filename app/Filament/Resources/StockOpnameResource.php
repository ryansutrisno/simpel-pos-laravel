<?php

namespace App\Filament\Resources;

use App\Enums\OpnameStatus;
use App\Filament\Resources\StockOpnameResource\Pages;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockOpname;
use App\Models\Store;
use App\Services\CurrentStoreService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Stock Opname';

    protected static ?string $pluralLabel = 'Stock Opname';

    protected static ?string $navigationGroup = 'Manajemen Stok';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Opname')
                            ->schema([
                                Forms\Components\Select::make('store_id')
                                    ->label('Store')
                                    ->options(Store::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default(fn (): ?int => app(CurrentStoreService::class)->getId())
                                    ->visible(fn (): bool => app(CurrentStoreService::class)->isSuperAdmin())
                                    ->live(),
                                Forms\Components\Hidden::make('store_id')
                                    ->default(fn (): ?int => app(CurrentStoreService::class)->getId())
                                    ->visible(fn (): bool => ! app(CurrentStoreService::class)->isSuperAdmin()),
                                Forms\Components\DatePicker::make('opname_date')
                                    ->label('Tanggal Opname')
                                    ->default(now())
                                    ->required(),
                            ]),

                        Forms\Components\Section::make('Item Produk')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship('items')
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->label('Produk')
                                            ->options(function (Forms\Get $get): array {
                                                $currentStoreService = app(CurrentStoreService::class);
                                                $storeId = $get('../../store_id') ?? $currentStoreService->getId();

                                                $availableProductIds = ProductStock::query()
                                                    ->when(
                                                        $storeId,
                                                        fn (Builder $query): Builder => $query->where('store_id', $storeId),
                                                        fn (Builder $query): Builder => $query->forCurrentStore()
                                                    )
                                                    ->where('quantity', '>', 0)
                                                    ->pluck('product_id');

                                                return Product::query()
                                                    ->where('is_active', true)
                                                    ->whereIn('id', $availableProductIds)
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                $currentStoreService = app(CurrentStoreService::class);
                                                $storeId = $get('../../store_id') ?? $currentStoreService->getId();

                                                if (! $state || ! $storeId) {
                                                    $set('system_stock', 0);

                                                    return;
                                                }

                                                $systemStock = ProductStock::query()
                                                    ->where('product_id', $state)
                                                    ->where('store_id', $storeId)
                                                    ->value('quantity');

                                                $set('system_stock', $systemStock ?? 0);
                                            }),
                                        Forms\Components\TextInput::make('system_stock')
                                            ->label('Stok Sistem')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->default(0),
                                        Forms\Components\TextInput::make('actual_stock')
                                            ->label('Stok Aktual')
                                            ->numeric()
                                            ->nullable()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                $systemStock = $get('system_stock') ?? 0;
                                                if ($state !== null) {
                                                    $set('difference', $state - $systemStock);
                                                }
                                            }),
                                        Forms\Components\TextInput::make('difference')
                                            ->label('Selisih')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->default(0)
                                            ->live()
                                            ->formatStateUsing(function ($state, Forms\Get $get) {
                                                $systemStock = $get('system_stock') ?? 0;
                                                $actualStock = $get('actual_stock');
                                                if ($actualStock !== null) {
                                                    return $actualStock - $systemStock;
                                                }

                                                return $state;
                                            }),
                                        Forms\Components\Textarea::make('note')
                                            ->label('Catatan')
                                            ->rows(1)
                                            ->columnSpan(2),
                                    ])
                                    ->columns(5)
                                    ->defaultItems(1)
                                    ->reorderable(false)
                                    ->addActionLabel('Tambah Item'),
                            ]),

                        Forms\Components\Section::make('Catatan Umum')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Detail')
                            ->schema([
                                Forms\Components\TextInput::make('opname_number')
                                    ->label('No. Opname')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visibleOn('edit'),
                                Forms\Components\Placeholder::make('status_label')
                                    ->label('Status')
                                    ->content(fn ($record) => $record?->status?->getLabel() ?? 'Draft')
                                    ->visibleOn('edit'),
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Dibuat')
                                    ->content(fn ($record) => $record?->created_at?->format('d M Y H:i') ?? '-')
                                    ->visibleOn('edit'),
                            ]),

                        Forms\Components\Section::make('User')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Dibuat Oleh')
                                    ->relationship('user', 'name')
                                    ->required()
                                    ->disabled(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('opname_number')
                    ->label('No. Opname')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('opname_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (OpnameStatus $state) => $state->getLabel())
                    ->color(fn (OpnameStatus $state) => $state->getColor()),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_difference')
                    ->label('Total Selisih')
                    ->state(fn (StockOpname $record): int => $record->getTotalDifference())
                    ->color(fn (int $state): string => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(OpnameStatus::class),
                Filter::make('opname_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('opname_date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('opname_date', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
                Tables\Actions\Action::make('complete')
                    ->label('Selesaikan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (StockOpname $record): bool => $record->isDraft())
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Stock Opname')
                    ->modalDescription('Stok produk akan disesuaikan sesuai data aktual. Tindakan ini tidak dapat dibatalkan.')
                    ->action(function (StockOpname $record) {
                        $record->complete();
                        Notification::make()
                            ->title('Stock Opname Selesai')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (StockOpname $record): bool => $record->isDraft())
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Stock Opname')
                    ->modalDescription('Tindakan ini tidak dapat dibatalkan.')
                    ->action(function (StockOpname $record) {
                        $record->update(['status' => OpnameStatus::Cancelled]);
                        Notification::make()
                            ->title('Stock Opname Dibatalkan')
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->forCurrentStore();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockOpnames::route('/'),
            'create' => Pages\CreateStockOpname::route('/create'),
            'view' => Pages\ViewStockOpname::route('/{record}'),
        ];
    }
}
