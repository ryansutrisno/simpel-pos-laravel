<?php

namespace App\Filament\Resources\PaymentGatewayConfigResource\Pages;

use App\Filament\Resources\PaymentGatewayConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentGatewayConfig extends EditRecord
{
    protected static string $resource = PaymentGatewayConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
