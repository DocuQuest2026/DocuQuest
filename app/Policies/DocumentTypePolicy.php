<?php

namespace App\Policies;

use App\Models\User;

class DocumentTypePolicy
{
    /**
     * Only administrators manage the documents on offer, since their fees and processing
     * times affect every request.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }
}
