<?php

namespace App\Filament\Resources\PaymentGatewayConfigResource\Pages;

use App\Filament\Resources\PaymentGatewayConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentGatewayConfigs extends ListRecords
{
    protected static string $resource = PaymentGatewayConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
