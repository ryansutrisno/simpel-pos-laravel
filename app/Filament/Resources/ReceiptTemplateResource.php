<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptTemplateResource\Pages;
use App\Models\ReceiptTemplate;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;

class ReceiptTemplateResource extends Resource
{
    protected static ?string $model = ReceiptTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Template Receipt';

    protected static ?string $modelLabel = 'receipt template';

    protected static ?string $pluralModelLabel = 'receipt templates';

    protected static ?string $navigationGroup = 'Pengaturan';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Section::make('Template Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Template Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000),

                        Forms\Components\Select::make('store_id')
                            ->label('Store')
                            ->options(Store::pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->helperText('Leave empty for global template'),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Set as Default Template')
                            ->helperText('Only one default template per store')
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),

                Section::make('Template Configuration')
                    ->schema([
                        Forms\Components\Tabs::make('Template Tabs')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Header')
                                    ->schema([
                                        Forms\Components\Toggle::make('template_data.header.show_logo')
                                            ->label('Show Logo')
                                            ->default(true),

                                        Forms\Components\Toggle::make('template_data.header.show_store_name')
                                            ->label('Show Store Name')
                                            ->default(true),

                                        Forms\Components\Toggle::make('template_data.header.show_store_address')
                                            ->label('Show Store Address')
                                            ->default(true),

                                        Forms\Components\Toggle::make('template_data.header.show_store_phone')
                                            ->label('Show Store Phone')
                                            ->default(true),

                                        Forms\Components\Toggle::make('template_data.header.show_tagline')
                                            ->label('Show Tagline')
                                            ->default(true),

                                        Forms\Components\Textarea::make('template_data.header.custom_header_message')
                                            ->label('Custom Header Message')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->placeholder('Special message at the top of receipt'),
                                    ]),

                                Forms\Components\Tabs\Tab::make('Body')
                                    ->schema([
                                        Forms\Components\Toggle::make('template_data.body.show_transaction_id')
                                            ->label('Show Transaction ID')
                                            ->default(true),

                                        Forms\Components\Toggle::make('template_data.body.show_date')
                                            ->label('Show Date & Time')
                                            ->default(true),

                                        Forms\Components\Toggle::make('template_data.body.show_cashier_name')
                                            ->label('Show Cashier Name')
                                            ->default(true),

                                        Forms\Components\Toggle::make('template_data.body.show_items_header')
                                            ->label('Show Items Header')
                                            ->default(true),

                                        Forms\Components\Select::make('template_data.body.item_format')
                                            ->label('Item Display Format')
                                            ->options([
                                                'name_price_quantity' => 'Name, Price & Quantity',
                                                'name_only' => 'Name Only',
                                                'price_only' => 'Price Only',
                                            ])
                                            ->default('name_price_quantity'),

                                        Forms\Components\Toggle::make('template_data.body.show_subtotal')
                                            ->label('Show Subtotal')
                                            ->default(true),
                                    ]),

                                Forms\Components\Tabs\Tab::make('Footer')
                                    ->schema([
                                        Forms\Components\Toggle::make('template_data.footer.show_payment_method')
                                            ->label('Show Payment Method')
                                            ->default(true),

                                        Forms\Components\Toggle::make('template_data.footer.show_cash_received')
                                            ->label('Show Cash Received')
                                            ->default(true),

                                        Forms\Components\Toggle::make('template_data.footer.show_change')
                                            ->label('Show Change Amount')
                                            ->default(true),

                                        Forms\Components\Textarea::make('template_data.footer.custom_footer_message')
                                            ->label('Custom Footer Message')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->placeholder('Thank you message or contact info'),

                                        Forms\Components\Toggle::make('template_data.footer.show_barcode')
                                            ->label('Show Barcode')
                                            ->default(true),

                                        Forms\Components\Toggle::make('template_data.footer.show_qr_code')
                                            ->label('Show QR Code')
                                            ->default(false),
                                    ]),

                                Forms\Components\Tabs\Tab::make('Styling')
                                    ->schema([
                                        Forms\Components\Select::make('template_data.styling.font_size')
                                            ->label('Font Size')
                                            ->options([
                                                'small' => 'Small',
                                                'normal' => 'Normal',
                                                'large' => 'Large',
                                            ])
                                            ->default('normal'),

                                        Forms\Components\Select::make('template_data.styling.text_alignment')
                                            ->label('Text Alignment')
                                            ->options([
                                                'left' => 'Left',
                                                'center' => 'Center',
                                                'right' => 'Right',
                                            ])
                                            ->default('left'),

                                        Forms\Components\Toggle::make('template_data.styling.bold_headers')
                                            ->label('Bold Headers')
                                            ->default(true),

                                        Forms\Components\Select::make('template_data.styling.separator_style')
                                            ->label('Separator Style')
                                            ->options([
                                                'dashes' => 'Dashes (===)',
                                                'dots' => 'Dots (...)',
                                                'line' => 'Line (---)',
                                            ])
                                            ->default('dashes'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store')
                    ->searchable()
                    ->sortable()
                    ->default('Global'),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('Store')
                    ->options(Store::pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default Template'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Validate template data before saving
                        $templateService = new \App\Services\ReceiptTemplateService;
                        $errors = $templateService->validateTemplateData($data['template_data'] ?? []);

                        if (! empty($errors)) {
                            Notification::make()
                                ->title('Template Validation Failed')
                                ->body(implode('<br>', $errors))
                                ->danger()
                                ->send();

                            throw new \Exception('Template validation failed');
                        }

                        return $data;
                    }),
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->url(fn (ReceiptTemplate $record): string => static::getUrl('preview', ['record' => $record->getRouteKey()]))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (ReceiptTemplate $record) {
                        if ($record->is_default) {
                            Notification::make()
                                ->title('Cannot Delete Default Template')
                                ->body('Please set another template as default before deleting this one.')
                                ->danger()
                                ->send();

                            return false;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->is_default) {
                                    Notification::make()
                                        ->title('Cannot Delete Default Template')
                                        ->body('One or more selected templates are default templates.')
                                        ->danger()
                                        ->send();

                                    return false;
                                }
                            }
                        }),
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
            'index' => Pages\ListReceiptTemplates::route('/'),
            'create' => Pages\CreateReceiptTemplate::route('/create'),
            'edit' => Pages\EditReceiptTemplate::route('/{record}/edit'),
            'view' => Pages\ViewReceiptTemplate::route('/{record}'),
            'preview' => Pages\PreviewReceiptTemplate::route('/{record}/preview'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_receipt::template');
    }
}
