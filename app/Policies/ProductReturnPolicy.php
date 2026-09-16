<?php

namespace App\Policies;

use App\Models\ProductReturn;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductReturnPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ProductReturn $return): bool
    {
        return $user->canAccessStore($return->transaction?->store_id);
    }
}
