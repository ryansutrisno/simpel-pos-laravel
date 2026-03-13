<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrinterConfigResource\Pages;
use App\Models\PrinterConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PrinterConfigResource extends Resource
{
    protected static ?string $model = PrinterConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-printer';

    protected static ?string $navigationLabel = 'Printer';

    protected static ?string $pluralLabel = 'Printer';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_printer::config');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Printer')
                    ->schema([
                        Forms\Components\Select::make('store_id')
                            ->label('Toko')
                            ->relationship('store', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Printer')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Printer Kasir Utama'),

                        Forms\Components\TextInput::make('model')
                            ->label('Model Printer')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Xprinter XP-58, Epson TM-T82, dll'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Koneksi')
                    ->schema([
                        Forms\Components\Select::make('connection_type')
                            ->label('Tipe Koneksi')
                            ->options([
                                'usb' => 'USB',
                                'bluetooth' => 'Bluetooth',
                                'network' => 'Network (Ethernet/WiFi)',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('address')
                            ->label('Alamat')
                            ->required()
                            ->maxLength(255)
                            ->placeholder(fn ($get) => match ($get('connection_type')) {
                                'usb' => '/dev/usb/lp0 atau COM3',
                                'bluetooth' => 'XX:XX:XX:XX:XX:XX',
                                'network' => '192.168.1.100',
                                default => '',
                            })
                            ->helperText(fn ($get) => match ($get('connection_type')) {
                                'usb' => 'Port USB printer (contoh: /dev/usb/lp0)',
                                'bluetooth' => 'MAC Address printer (contoh: 00:11:22:33:44:55)',
                                'network' => 'IP Address printer',
                                default => 'Pilih tipe koneksi terlebih dahulu',
                            }),

                        Forms\Components\TextInput::make('port')
                            ->label('Port')
                            ->numeric()
                            ->nullable()
                            ->visible(fn ($get) => $get('connection_type') === 'network')
                            ->placeholder('9100')
                            ->helperText('Port untuk koneksi network (default: 9100)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Default')
                            ->helperText('Printer default akan digunakan untuk print receipt')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pengaturan Tambahan')
                    ->schema([
                        Forms\Components\KeyValue::make('settings')
                            ->label('Konfigurasi Tambahan')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->nullable()
                            ->helperText('Konfigurasi khusus untuk printer (optional)'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Toko')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('connection_type')
                    ->label('Koneksi')
                    ->colors([
                        'primary' => 'usb',
                        'success' => 'bluetooth',
                        'warning' => 'network',
                    ]),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

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
                Tables\Filters\SelectFilter::make('store')
                    ->relationship('store', 'name')
                    ->label('Toko'),

                Tables\Filters\SelectFilter::make('connection_type')
                    ->label('Tipe Koneksi')
                    ->options([
                        'usb' => 'USB',
                        'bluetooth' => 'Bluetooth',
                        'network' => 'Network',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrinterConfigs::route('/'),
            'create' => Pages\CreatePrinterConfig::route('/create'),
            'edit' => Pages\EditPrinterConfig::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
