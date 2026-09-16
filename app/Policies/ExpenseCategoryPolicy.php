<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpenseCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function view(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function restore(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function forceDelete(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
