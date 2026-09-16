<?php

namespace App\Policies;

use App\Models\PaymentGatewayConfig;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentGatewayConfigPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function view(User $user, PaymentGatewayConfig $paymentGatewayConfig): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, PaymentGatewayConfig $paymentGatewayConfig): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, PaymentGatewayConfig $paymentGatewayConfig): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function restore(User $user, PaymentGatewayConfig $paymentGatewayConfig): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function forceDelete(User $user, PaymentGatewayConfig $paymentGatewayConfig): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
