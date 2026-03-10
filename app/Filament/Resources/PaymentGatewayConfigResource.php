<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentGatewayConfigResource\Pages;
use App\Models\PaymentGatewayConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentGatewayConfigResource extends Resource
{
    protected static ?string $model = PaymentGatewayConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Payment Gateway';

    protected static ?string $pluralLabel = 'Payment Gateway';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Provider')
                    ->schema([
                        Forms\Components\Select::make('store_id')
                            ->label('Toko')
                            ->relationship('store', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('provider')
                            ->label('Provider')
                            ->options([
                                PaymentGatewayConfig::PROVIDER_MAYAR => 'Mayar',
                                PaymentGatewayConfig::PROVIDER_MIDTRANS => 'Midtrans',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),

                        Forms\Components\Toggle::make('is_sandbox')
                            ->label('Sandbox Mode')
                            ->helperText('Aktifkan untuk mode testing')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Konfigurasi Mayar')
                    ->schema([
                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->required(),

                        Forms\Components\TextInput::make('webhook_url')
                            ->label('Webhook URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://your-domain.com/webhook/mayar')
                            ->nullable(),
                    ])
                    ->columns(1)
                    ->visible(fn ($get) => $get('provider') === PaymentGatewayConfig::PROVIDER_MAYAR),

                Forms\Components\Section::make('Konfigurasi Midtrans')
                    ->schema([
                        Forms\Components\TextInput::make('provider_config.server_key')
                            ->label('Server Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->required()
                            ->formatStateUsing(fn ($state) => $state ?? '')
                            ->dehydrateStateUsing(fn ($state) => $state ?? ''),

                        Forms\Components\TextInput::make('provider_config.client_key')
                            ->label('Client Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->required()
                            ->formatStateUsing(fn ($state) => $state ?? '')
                            ->dehydrateStateUsing(fn ($state) => $state ?? ''),

                        Forms\Components\Select::make('provider_config.environment')
                            ->label('Environment')
                            ->options([
                                'sandbox' => 'Sandbox',
                                'production' => 'Production',
                            ])
                            ->default('sandbox')
                            ->required()
                            ->formatStateUsing(fn ($state) => $state ?? 'sandbox')
                            ->dehydrateStateUsing(fn ($state) => $state ?? 'sandbox'),
                    ])
                    ->columns(1)
                    ->visible(fn ($get) => $get('provider') === PaymentGatewayConfig::PROVIDER_MIDTRANS),

                Forms\Components\Section::make('Metode Pembayaran')
                    ->schema([
                        Forms\Components\CheckboxList::make('enabled_methods')
                            ->label('Metode yang Diaktifkan')
                            ->options(fn ($get) => match ($get('provider')) {
                                PaymentGatewayConfig::PROVIDER_MAYAR => [
                                    'qris' => 'QRIS',
                                    'bank_transfer' => 'Bank Transfer',
                                    'e_wallet' => 'E-Wallet',
                                ],
                                PaymentGatewayConfig::PROVIDER_MIDTRANS => [
                                    'credit_card' => 'Credit Card',
                                    'gopay' => 'GoPay',
                                    'shopeepay' => 'ShopeePay',
                                    'qris' => 'QRIS',
                                    'bank_transfer' => 'Bank Transfer',
                                ],
                                default => [],
                            })
                            ->bulkToggleable()
                            ->columns(2),
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

                Tables\Columns\BadgeColumn::make('provider')
                    ->label('Provider')
                    ->colors([
                        'primary' => PaymentGatewayConfig::PROVIDER_MAYAR,
                        'success' => PaymentGatewayConfig::PROVIDER_MIDTRANS,
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_sandbox')
                    ->label('Sandbox')
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

                Tables\Filters\SelectFilter::make('provider')
                    ->label('Provider')
                    ->options([
                        PaymentGatewayConfig::PROVIDER_MAYAR => 'Mayar',
                        PaymentGatewayConfig::PROVIDER_MIDTRANS => 'Midtrans',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),

                Tables\Filters\TernaryFilter::make('is_sandbox')
                    ->label('Sandbox Mode'),
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
            'index' => Pages\ListPaymentGatewayConfigs::route('/'),
            'create' => Pages\CreatePaymentGatewayConfig::route('/create'),
            'edit' => Pages\EditPaymentGatewayConfig::route('/{record}/edit'),
        ];
    }
}
