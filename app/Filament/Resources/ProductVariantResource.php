<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductVariantResource\Pages;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Manajemen Produk';

    protected static ?string $navigationLabel = 'Varian Produk';

    protected static ?string $modelLabel = 'Varian Produk';

    protected static ?string $pluralModelLabel = 'Varian Produk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('variant_name')
                    ->label('Nama Varian')
                    ->placeholder('Contoh: 1 Liter, Merah, Size M')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('sku')
                    ->label('SKU / Barcode')
                    ->placeholder('Contoh: MIN-1L-001')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\Section::make('Harga & Stok')
                    ->schema([
                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Harga Beli')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->maxValue(999999999.99),

                        Forms\Components\TextInput::make('selling_price')
                            ->label('Harga Jual')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->maxValue(999999999.99),

                        Forms\Components\TextInput::make('stock')
                            ->label('Stok')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\TextInput::make('low_stock_threshold')
                            ->label('Batas Stok Minimum')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->default(10)
                            ->minValue(1),
                    ])
                    ->columns(2),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Nonaktifkan untuk menyembunyikan varian ini dari POS'),
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

                Tables\Columns\TextColumn::make('variant_name')
                    ->label('Varian')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (ProductVariant $record): string => match (true) {
                        $record->stock <= 0 => 'danger',
                        $record->stock <= $record->low_stock_threshold => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->boolean()
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Stok Menipis')
                    ->toggle()
                    ->query(fn ($query) => $query->whereColumn('stock', '<=', 'low_stock_threshold')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductVariants::route('/'),
            'create' => Pages\CreateProductVariant::route('/create'),
            'view' => Pages\ViewProductVariant::route('/{record}'),
            'edit' => Pages\EditProductVariant::route('/{record}/edit'),
        ];
    }
}
