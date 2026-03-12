<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Models\ReceiptTemplate;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Pengaturan Toko';

    protected static ?string $pluralLabel = 'Pengaturan Toko';

    protected static ?string $navigationGroup = 'Pengaturan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Toko')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Toko')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telepon')
                            ->tel(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Pengaturan Receipt')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo Toko')
                            ->image()
                            ->directory('store-logos')
                            ->maxSize(1024)
                            ->helperText('Upload logo toko untuk ditampilkan di receipt')
                            ->nullable(),

                        Forms\Components\TextInput::make('receipt_tagline')
                            ->label('Tagline Toko')
                            ->maxLength(255)
                            ->helperText('Tagline akan ditampilkan di bagian atas receipt')
                            ->nullable(),

                        Forms\Components\TextInput::make('receipt_header_message')
                            ->label('Pesan Header Receipt')
                            ->maxLength(255)
                            ->helperText('Pesan khusus di bagian atas receipt')
                            ->nullable(),

                        Forms\Components\TextInput::make('receipt_footer_message')
                            ->label('Pesan Footer Receipt')
                            ->maxLength(255)
                            ->helperText('Pesan di bagian bawah receipt, contoh: "Terima kasih"')
                            ->nullable(),

                        Forms\Components\Select::make('receipt_template_id')
                            ->label('Template Receipt')
                            ->options(ReceiptTemplate::where('is_active', true)->pluck('name', 'id'))
                            ->helperText('Pilih template yang akan digunakan untuk receipt')
                            ->nullable(),

                        Forms\Components\Select::make('receipt_width')
                            ->label('Lebar Receipt')
                            ->options([
                                '58mm' => '58mm',
                                '80mm' => '80mm',
                            ])
                            ->default('58mm'),

                        Forms\Components\Toggle::make('show_cashier_name')
                            ->label('Tampilkan Nama Kasir')
                            ->helperText('Tampilkan nama kasir di receipt')
                            ->default(true),

                        Forms\Components\Toggle::make('show_barcode')
                            ->label('Tampilkan Barcode')
                            ->helperText('Tampilkan barcode transaction ID di receipt')
                            ->default(true),

                        Forms\Components\Toggle::make('show_qr_code')
                            ->label('Tampilkan QR Code')
                            ->helperText('Tampilkan QR code di receipt')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pengaturan Printer')
                    ->schema([
                        Forms\Components\TextInput::make('printer_device_id')
                            ->label('ID Perangkat Printer Bluetooth')
                            ->helperText('ID perangkat printer akan tersimpan otomatis saat Anda menghubungkan printer di halaman POS')
                            ->readOnly(),
                        Forms\Components\Placeholder::make('printer_help')
                            ->label('Cara Menghubungkan Printer')
                            ->content('1. Pastikan printer Bluetooth Anda dalam mode pairing
2. Buka halaman POS
3. Klik tombol "Hubungkan Printer"
4. Pilih printer dari daftar yang muncul
5. Printer akan terhubung dan tersimpan otomatis'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Pengaturan Pajak (PPN)')
                    ->schema([
                        Forms\Components\Toggle::make('tax_enabled')
                            ->label('Aktifkan Pajak')
                            ->helperText('Aktifkan untuk menambahkan PPN ke setiap transaksi')
                            ->default(false)
                            ->reactive(),

                        Forms\Components\TextInput::make('tax_rate')
                            ->label('Tarif Pajak (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->default(10)
                            ->helperText('Tarif pajak PPN (default: 10%)')
                            ->visible(fn ($get) => $get('tax_enabled')),

                        Forms\Components\TextInput::make('tax_name')
                            ->label('Nama Pajak')
                            ->maxLength(50)
                            ->default('PPN')
                            ->helperText('Nama pajak yang ditampilkan di receipt')
                            ->visible(fn ($get) => $get('tax_enabled')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Toko')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('receipt_template.name')
                    ->label('Template Receipt')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->circular()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('printer_device_id')
                    ->label('Printer Terhubung')
                    ->badge()
                    ->color(fn (string $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state ? 'Terhubung' : 'Tidak Terhubung'),
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
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
