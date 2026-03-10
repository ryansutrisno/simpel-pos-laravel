<?php

namespace App\Filament\Resources\PaymentGatewayConfigResource\Pages;

use App\Filament\Resources\PaymentGatewayConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentGatewayConfig extends CreateRecord
{
    protected static string $resource = PaymentGatewayConfigResource::class;
}
