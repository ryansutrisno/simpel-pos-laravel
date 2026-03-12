<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use App\Filament\Resources\StockAdjustmentResource;
use App\Models\StockAdjustment;
use App\Services\CurrentStoreService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['store_id'] = $data['store_id'] ?? app(CurrentStoreService::class)->getId();
        $data['user_id'] = Auth::id();
        $data['adjustment_number'] = 'ADJ-'.str_pad(StockAdjustment::count() + 1, 5, '0', STR_PAD_LEFT);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
