<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductStockResource\Pages;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Store;
use App\Services\CurrentStoreService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductStockResource extends Resource
{
    protected static ?string $model = ProductStock::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventori';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $currentStoreId = app(CurrentStoreService::class)->getId();

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Stok')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Produk')
                            ->options(function () {
                                return Product::where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $product = Product::find($state);
                                if ($product && ! $product->has_variants) {
                                    $set('variant_id', null);
                                }
                            }),

                        Forms\Components\Select::make('variant_id')
                            ->label('Varian')
                            ->options(function (callable $get) {
                                $productId = $get('product_id');
                                if (! $productId) {
                                    return [];
                                }
                                $product = Product::find($productId);
                                if (! $product || ! $product->has_variants) {
                                    return [];
                                }

                                return $product->variants()->pluck('name', 'id');
                            })
                            ->searchable()
                            ->visible(function (callable $get) {
                                $productId = $get('product_id');
                                if (! $productId) {
                                    return false;
                                }
                                $product = Product::find($productId);

                                return $product && $product->has_variants;
                            }),

                        Forms\Components\Select::make('store_id')
                            ->label('Toko')
                            ->options(function () use ($user) {
                                if ($user->isSuperAdmin()) {
                                    return Store::pluck('name', 'id');
                                }

                                return Store::where('id', $user->current_store_id)->pluck('name', 'id');
                            })
                            ->default($currentStoreId)
                            ->disabled(! $user->isSuperAdmin())
                            ->dehydrated()
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Jumlah Stok')
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Jumlah Stok')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\TextInput::make('min_stock')
                            ->label('Stok Minimum')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Peringatan akan muncul jika stok di bawah nilai ini'),

                        Forms\Components\TextInput::make('max_stock')
                            ->label('Stok Maksimum')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Kosongkan jika tidak ada batasan'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('variant.name')
                    ->label('Varian')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('Toko')
                    ->sortable()
                    ->visible(fn () => auth()->user()->isSuperAdmin()),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Stok')
                    ->numeric(2)
                    ->sortable()
                    ->badge()
                    ->color(fn (ProductStock $record): string => match ($record->stock_status) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Min. Stok')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('max_stock')
                    ->label('Max. Stok')
                    ->numeric(2)
                    ->sortable()
                    ->placeholder('∞')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'out_of_stock' => 'Habis',
                        'low_stock' => 'Stok Rendah',
                        default => 'Tersedia',
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Stok Rendah')
                    ->query(fn (Builder $query): Builder => $query->lowStock()),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Stok Habis')
                    ->query(fn (Builder $query): Builder => $query->outOfStock()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductStocks::route('/'),
            'create' => Pages\CreateProductStock::route('/create'),
            'edit' => Pages\EditProductStock::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user->isSuperAdmin()) {
            $storeId = $user->current_store_id;
            if ($storeId) {
                $query->where('store_id', $storeId);
            }
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create', ProductStock::class);
    }
}
