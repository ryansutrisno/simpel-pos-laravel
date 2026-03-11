<?php

namespace App\Filament\Resources\AppSettingsResource\Pages;

use App\Filament\Resources\AppSettingsResource;
use App\Models\AppSettings;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

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

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        parent::save($shouldRedirect, $shouldSendSavedNotification);

        Cache::forget('app.settings');

        $settings = AppSettings::getInstance();
        config(['app.name' => $settings->app_name]);
        config(['app.timezone' => $settings->timezone]);

        $this->redirect(static::getUrl(['record' => $settings->id]));
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
