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
}
