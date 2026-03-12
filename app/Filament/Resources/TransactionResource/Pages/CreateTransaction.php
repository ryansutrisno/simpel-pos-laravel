<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Services\CurrentStoreService;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['store_id'] ?? null)) {
            $data['store_id'] = app(CurrentStoreService::class)->getId();
        }

        return $data;
    }
}
