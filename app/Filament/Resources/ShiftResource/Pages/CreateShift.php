<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use App\Filament\Resources\ShiftResource;
use App\Services\ShiftService;
use Filament\Resources\Pages\CreateRecord;

class CreateShift extends CreateRecord
{
    protected static string $resource = ShiftResource::class;

    protected static ?string $title = 'Buka Shift Baru';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['opened_at'] = now();

        return $data;
    }

    protected function beforeCreate(): void
    {
        if (! ShiftService::canOpenShift(auth()->id())) {
            $this->halt();
            \Filament\Notifications\Notification::make()
                ->title('Tidak bisa membuka shift')
                ->body('Anda masih memiliki shift yang aktif. Tutup shift sebelumnya terlebih dahulu.')
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Shift berhasil dibuka';
    }
}
