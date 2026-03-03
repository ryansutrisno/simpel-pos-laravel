<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Manajemen Produk';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $pluralLabel = 'Produk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                        Forms\Components\TextInput::make('barcode')
                            ->label('Kode Barang')
                            ->unique(table: 'products', column: 'barcode', ignoreRecord: true)
                            ->maxLength(50),
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori Produk')
                            ->relationship('category', 'name')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi Produk')
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Harga & Stok')
                    ->schema([
                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Harga Modal')
                            ->numeric()
                            ->required()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('selling_price')
                            ->label('Harga Jual')
                            ->numeric()
                            ->required()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('stock')
                            ->label('Stok Produk')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('reorder_point')
                            ->label('Batas Reorder')
                            ->numeric()
                            ->integer()
                            ->default(10)
                            ->helperText('Stok minimum sebelum alert reorder'),
                        Forms\Components\TextInput::make('reorder_quantity')
                            ->label('Jumlah Reorder Ideal')
                            ->numeric()
                            ->integer()
                            ->default(50)
                            ->helperText('Jumlah ideal saat reorder'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
                Forms\Components\Section::make('Varian Produk')
                    ->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship('variants')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('variant_name')
                                    ->label('Nama Varian')
                                    ->required(),
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU')
                                    ->unique(ignoreRecord: true)
                                    ->required(),
                                Forms\Components\TextInput::make('purchase_price')
                                    ->label('Harga Beli')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                                Forms\Components\TextInput::make('selling_price')
                                    ->label('Harga Jual')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                                Forms\Components\TextInput::make('stock')
                                    ->label('Stok')
                                    ->numeric()
                                    ->integer()
                                    ->default(0),
                                Forms\Components\TextInput::make('low_stock_threshold')
                                    ->label('Batas Stok Min')
                                    ->numeric()
                                    ->integer()
                                    ->default(10),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Varian')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['variant_name'] ?? null),
                    ])
                    ->collapsible()
                    ->collapsed(fn (string $operation): bool => $operation === 'create'),

                Forms\Components\Section::make('Gambar Produk')
                    ->schema([
                        FileUpload::make('image')
                            ->directory('products')
                            ->image()
                            ->maxSize(2048)
                            ->label('Foto Produk'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Foto Produk')
                    ->disk('public'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori Produk')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Harga Modal')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok Produk')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record) => $record->stock <= 0 ? 'danger' : ($record->stock <= $record->low_stock_threshold ? 'warning' : null))
                    ->icon(fn ($record) => $record->stock <= $record->low_stock_threshold ? 'heroicon-o-exclamation-triangle' : null),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Produk Dihapus'),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori Produk')
                    ->relationship('category', 'name'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stok Menipis')
                    ->query(fn ($query) => $query->whereColumn('stock', '<=', 'low_stock_threshold'))
                    ->toggle(),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Stok Habis')
                    ->query(fn ($query) => $query->where('stock', '<=', 0))
                    ->toggle(),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
                Tables\Actions\RestoreAction::make()->label('Pulihkan'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
