<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use App\Filament\Resources\ShiftResource;
use App\Services\ShiftService;
use Filament\Resources\Pages\EditRecord;

class EditShift extends EditRecord
{
    protected static string $resource = ShiftResource::class;

    protected static ?string $title = 'Tutup Shift';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        if ($record->isOpen()) {
            $data['expected_cash'] = ShiftService::calculateExpectedCash($record->id);
            $data['difference'] = $data['closing_cash'] - $data['expected_cash'];
            $data['status'] = 'closed';
            $data['closed_at'] = now();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Shift berhasil ditutup';
    }
}
