<?php

namespace App\Filament\Resources\AppSettingsResource\Pages;

use App\Filament\Resources\AppSettingsResource;
use App\Models\AppSettings;
use Filament\Resources\Pages\EditRecord;

class EditAppSettings extends EditRecord
{
    protected static string $resource = AppSettingsResource::class;

    protected static ?string $title = 'Pengaturan Aplikasi';

    public function mount(int|string|null $record = null): void
    {
        $settings = AppSettings::getInstance();
        $this->record = $settings;

        $this->authorizeAccess();

        $this->form->fill($this->record->attributesToArray());
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
