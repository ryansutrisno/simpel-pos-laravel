<?php

namespace App\Filament\Resources;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentType;
use App\Filament\Resources\StockAdjustmentResource\Pages;
use App\Models\Product;
use App\Models\StockAdjustment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static ?string $navigationLabel = 'Penyesuaian Stok';

    protected static ?string $pluralLabel = 'Penyesuaian Stok';

    protected static ?string $navigationGroup = 'Manajemen Stok';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Penyesuaian')
                            ->schema([
                                Forms\Components\Select::make('store_id')
                                    ->label('Toko')
                                    ->relationship('store', 'name')
                                    ->searchable()
                                    ->required()
                                    ->default(fn (): ?int => app(\App\Services\CurrentStoreService::class)->getId())
                                    ->hidden(fn (): bool => ! app(\App\Services\CurrentStoreService::class)->isSuperAdmin()),
                                Forms\Components\Select::make('type')
                                    ->label('Jenis')
                                    ->options(AdjustmentType::class)
                                    ->enum(AdjustmentType::class)
                                    ->required()
                                    ->live()
                                    ->default(AdjustmentType::Decrease),
                                Forms\Components\Select::make('reason')
                                    ->label('Alasan')
                                    ->options(AdjustmentReason::class)
                                    ->enum(AdjustmentReason::class)
                                    ->required()
                                    ->searchable(),
                                Forms\Components\DatePicker::make('adjustment_date')
                                    ->label('Tanggal')
                                    ->default(now())
                                    ->required(),
                            ])
                            ->columns(4),

                        Forms\Components\Section::make('Item Produk')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship('items')
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->label('Produk')
                                            ->relationship(
                                                name: 'product',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                                    ->where('is_active', true)
                                                    ->whereHas('stocks', fn (Builder $stockQuery): Builder => $stockQuery
                                                        ->forCurrentStore()
                                                        ->where('quantity', '>', 0)
                                                    ),
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                $product = Product::find($state);
                                                if ($product) {
                                                    $set('current_stock', $product->stocks()->forCurrentStore()->value('quantity') ?? 0);
                                                }
                                            }),
                                        Forms\Components\TextInput::make('current_stock')
                                            ->label('Stok Saat Ini')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->default(0),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Jumlah')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                $currentStock = $get('current_stock') ?? 0;
                                                $type = $get('../../type');

                                                if ($type === AdjustmentType::Increase->value) {
                                                    $set('stock_after', $currentStock + $state);
                                                } else {
                                                    $set('stock_after', max(0, $currentStock - $state));
                                                }
                                            }),
                                        Forms\Components\TextInput::make('stock_after')
                                            ->label('Stok Setelah')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->default(0),
                                        Forms\Components\Textarea::make('notes')
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
                                Forms\Components\TextInput::make('adjustment_number')
                                    ->label('No. Penyesuaian')
                                    ->disabled()
                                    ->dehydrated(false)
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
                                    ->default(Auth::id())
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
                Tables\Columns\TextColumn::make('adjustment_number')
                    ->label('No. Penyesuaian')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('adjustment_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (AdjustmentType $state) => $state->getLabel())
                    ->color(fn (AdjustmentType $state) => $state->getColor()),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan')
                    ->badge()
                    ->formatStateUsing(fn (AdjustmentReason $state) => $state->getLabel())
                    ->color(fn (AdjustmentReason $state) => $state->getColor()),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items')
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis')
                    ->options(AdjustmentType::class),
                Tables\Filters\SelectFilter::make('reason')
                    ->label('Alasan')
                    ->options(AdjustmentReason::class),
                Filter::make('adjustment_date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('adjustment_date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('adjustment_date', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
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
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
            'view' => Pages\ViewStockAdjustment::route('/{record}'),
        ];
    }
}
