<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpensePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function restore(User $user, Expense $expense): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function forceDelete(User $user, Expense $expense): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
