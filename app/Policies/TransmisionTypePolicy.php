<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TransmisionType;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransmisionTypePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_transmision::type');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TransmisionType $transmisionType): bool
    {
        return $user->can('view_transmision::type');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_transmision::type');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TransmisionType $transmisionType): bool
    {
        return $user->can('update_transmision::type');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TransmisionType $transmisionType): bool
    {
        return $user->can('delete_transmision::type');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_transmision::type');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, TransmisionType $transmisionType): bool
    {
        return $user->can('force_delete_transmision::type');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_transmision::type');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, TransmisionType $transmisionType): bool
    {
        return $user->can('restore_transmision::type');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_transmision::type');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, TransmisionType $transmisionType): bool
    {
        return $user->can('replicate_transmision::type');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_transmision::type');
    }
}
