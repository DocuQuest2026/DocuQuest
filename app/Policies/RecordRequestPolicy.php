<?php

namespace App\Policies;

use App\Models\RecordRequest;
use App\Models\User;

class RecordRequestPolicy
{
    /**
     * Registrar staff and administrators can see the list of student record requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOfficeUser();
    }

    public function view(User $user, RecordRequest $recordRequest): bool
    {
        return $user->isOfficeUser();
    }
}
