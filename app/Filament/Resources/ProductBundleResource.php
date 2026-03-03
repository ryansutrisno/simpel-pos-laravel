<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductBundleResource\Pages;
use App\Models\ProductBundle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductBundleResource extends Resource
{
    protected static ?string $model = ProductBundle::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Manajemen Produk';

    protected static ?string $navigationLabel = 'Paket Produk';

    protected static ?string $modelLabel = 'Paket Produk';

    protected static ?string $pluralModelLabel = 'Paket Produk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Paket')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Paket')
                            ->placeholder('Contoh: Beli 2 Gratis 1, Paket Lebaran')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Jelaskan detail paket ini')
                            ->rows(2),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Nonaktifkan untuk menonaktifkan sementara'),
                    ]),

                Forms\Components\Section::make('Periode Berlaku')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Berakhir (Opsional)')
                            ->helperText('Kosongkan jika paket berlaku selamanya'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pengaturan Diskon')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->label('Tipe Diskon')
                            ->required()
                            ->options([
                                'percentage' => 'Persentase (%)',
                                'fixed_amount' => 'Nominal Tetap (Rp)',
                                'free_item' => 'Gratis Item',
                            ])
                            ->live(),

                        Forms\Components\TextInput::make('discount_value')
                            ->label('Nilai Diskon')
                            ->required()
                            ->numeric()
                            ->prefix(fn ($get) => match ($get('discount_type')) {
                                'percentage' => '%',
                                default => 'Rp',
                            })
                            ->hidden(fn ($get) => $get('discount_type') === 'free_item')
                            ->helperText(fn ($get) => match ($get('discount_type')) {
                                'percentage' => 'Masukkan angka 1-100',
                                'fixed_amount' => 'Masukkan nominal diskon',
                                default => null,
                            }),

                        Forms\Components\TextInput::make('min_quantity')
                            ->label('Jumlah Minimum')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->default(2)
                            ->minValue(1)
                            ->helperText('Jumlah item minimum untuk memicu bundle'),

                        Forms\Components\TextInput::make('priority')
                            ->label('Prioritas')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Bundle dengan prioritas lebih tinggi akan diprioritaskan'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Item Paket')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Produk')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\Select::make('variant_id')
                                    ->label('Varian (Opsional)')
                                    ->relationship('variant', 'variant_name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Semua varian'),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Jumlah')
                                    ->required()
                                    ->numeric()
                                    ->integer()
                                    ->default(1)
                                    ->minValue(1),

                                Forms\Components\Toggle::make('is_free')
                                    ->label('Gratis')
                                    ->default(false)
                                    ->helperText('Centang jika item ini gratis'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Item')
                            ->reorderableWithButtons()
                            ->collapsible(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Paket')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('discount_type')
                    ->label('Tipe Diskon')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percentage' => 'Persentase',
                        'fixed_amount' => 'Nominal',
                        'free_item' => 'Gratis',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'info',
                        'fixed_amount' => 'warning',
                        'free_item' => 'success',
                    }),

                Tables\Columns\TextColumn::make('discount_value')
                    ->label('Nilai')
                    ->formatStateUsing(function (ProductBundle $record): string {
                        if ($record->isFreeItem()) {
                            return '-';
                        }
                        if ($record->isPercentage()) {
                            return $record->discount_value.'%';
                        }

                        return 'Rp '.number_format($record->discount_value, 0, ',', '.');
                    }),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Berakhir')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Selamanya'),

                Tables\Columns\IconColumn::make('isValid')
                    ->label('Berlaku')
                    ->boolean()
                    ->getStateUsing(fn (ProductBundle $record) => $record->isValid()),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('discount_type')
                    ->label('Tipe Diskon')
                    ->options([
                        'percentage' => 'Persentase',
                        'fixed_amount' => 'Nominal',
                        'free_item' => 'Gratis',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->boolean(),

                Tables\Filters\Filter::make('valid_now')
                    ->label('Berlaku Sekarang')
                    ->toggle()
                    ->query(fn ($query) => $query
                        ->where('start_date', '<=', now())
                        ->where(function ($q) {
                            $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                        })
                        ->where('is_active', true)),
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
            ->defaultSort('priority', 'desc');
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
            'index' => Pages\ListProductBundles::route('/'),
            'create' => Pages\CreateProductBundle::route('/create'),
            'view' => Pages\ViewProductBundle::route('/{record}'),
            'edit' => Pages\EditProductBundle::route('/{record}/edit'),
        ];
    }
}
