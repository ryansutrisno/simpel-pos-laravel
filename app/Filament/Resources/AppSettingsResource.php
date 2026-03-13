<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppSettingsResource\Pages;
use App\Models\AppSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;

class AppSettingsResource extends Resource
{
    protected static ?string $model = AppSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Aplikasi';

    protected static ?string $pluralLabel = 'Pengaturan Aplikasi';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_app::settings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Aplikasi')
                    ->schema([
                        Forms\Components\TextInput::make('app_name')
                            ->label('Nama Aplikasi')
                            ->required()
                            ->maxLength(255)
                            ->default('Simpel POS'),

                        Forms\Components\FileUpload::make('app_logo')
                            ->label('Logo Aplikasi')
                            ->image()
                            ->directory('app-logos')
                            ->maxSize(1024)
                            ->nullable(),

                        Forms\Components\FileUpload::make('favicon')
                            ->label('Favicon')
                            ->image()
                            ->directory('app-favicons')
                            ->maxSize(512)
                            ->nullable(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Pengaturan Regional')
                    ->schema([
                        Forms\Components\Select::make('timezone')
                            ->label('Timezone')
                            ->options([
                                'Asia/Jakarta' => 'Asia/Jakarta (WIB)',
                                'Asia/Makassar' => 'Asia/Makassar (WITA)',
                                'Asia/Jayapura' => 'Asia/Jayapura (WIT)',
                                'UTC' => 'UTC',
                            ])
                            ->default('Asia/Jakarta')
                            ->required(),

                        Forms\Components\Select::make('date_format')
                            ->label('Format Tanggal')
                            ->options([
                                'd/m/Y' => 'DD/MM/YYYY (25/12/2024)',
                                'Y-m-d' => 'YYYY-MM-DD (2024-12-25)',
                                'd-m-Y' => 'DD-MM-YYYY (25-12-2024)',
                                'm/d/Y' => 'MM/DD/YYYY (12/25/2024)',
                            ])
                            ->default('d/m/Y')
                            ->required(),

                        Forms\Components\Select::make('time_format')
                            ->label('Format Waktu')
                            ->options([
                                'H:i' => '24 Jam (14:30)',
                                'h:i A' => '12 Jam (02:30 PM)',
                            ])
                            ->default('H:i')
                            ->required(),

                        Forms\Components\Select::make('currency')
                            ->label('Mata Uang')
                            ->options([
                                'IDR' => 'Indonesian Rupiah (IDR)',
                                'USD' => 'US Dollar (USD)',
                                'EUR' => 'Euro (EUR)',
                            ])
                            ->default('IDR')
                            ->required(),

                        Forms\Components\Select::make('currency_format')
                            ->label('Format Mata Uang')
                            ->options([
                                'id_ID' => 'Indonesian (id_ID)',
                                'en_US' => 'English US (en_US)',
                            ])
                            ->default('id_ID')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pengaturan Email')
                    ->schema([
                        Forms\Components\TextInput::make('email_from')
                            ->label('Email Pengirim')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('noreply@example.com')
                            ->nullable(),

                        Forms\Components\TextInput::make('email_from_name')
                            ->label('Nama Pengirim')
                            ->maxLength(255)
                            ->placeholder('Simpel POS')
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pengaturan Maintenance')
                    ->schema([
                        Forms\Components\Toggle::make('maintenance_mode')
                            ->label('Mode Maintenance')
                            ->helperText('Aktifkan untuk mematikan akses ke aplikasi')
                            ->default(false),
                    ])
                    ->columns(1),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\EditAppSettings::route('/'),
            'edit' => Pages\EditAppSettings::route('/edit'),
        ];
    }
}
