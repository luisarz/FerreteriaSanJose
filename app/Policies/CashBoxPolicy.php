<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CashBox;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashBoxPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_cashbox');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CashBox $cashBox): bool
    {
        return $user->can('view_cashbox');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_cashbox');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CashBox $cashBox): bool
    {
        return $user->can('update_cashbox');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CashBox $cashBox): bool
    {
        return $user->can('delete_cashbox');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_cashbox');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, CashBox $cashBox): bool
    {
        return $user->can('force_delete_cashbox');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_cashbox');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, CashBox $cashBox): bool
    {
        return $user->can('restore_cashbox');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_cashbox');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, CashBox $cashBox): bool
    {
        return $user->can('replicate_cashbox');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_cashbox');
    }
}
