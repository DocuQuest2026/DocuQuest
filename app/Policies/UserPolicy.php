<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Only administrators manage the registrar's office accounts.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Administrators may edit office accounts; student accounts are managed by the students themselves.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() && $model->isOfficeUser();
    }

    /**
     * Administrators may set a new password for any account, office or student.
     */
    public function resetPassword(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Administrators may delete an office account, but never their own: that would lock
     * them out of managing accounts.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $model->isOfficeUser() && ! $user->is($model);
    }

    /**
     * Administrators may recover a previously deleted office account.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin() && $model->trashed();
    }

    /**
     * Administrators may permanently erase an office account that was already deleted.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->isAdmin() && $model->trashed() && ! $user->is($model);
    }
}
