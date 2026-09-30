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

    public function approve(User $user, RecordRequest $recordRequest): bool
    {
        return $user->isOfficeUser() && $recordRequest->isApprovable();
    }

    public function reject(User $user, RecordRequest $recordRequest): bool
    {
        return $user->isOfficeUser() && $recordRequest->isRejectable();
    }

    public function release(User $user, RecordRequest $recordRequest): bool
    {
        return $user->isOfficeUser() && $recordRequest->isReleasable();
    }

    public function confirmCancellation(User $user, RecordRequest $recordRequest): bool
    {
        return $user->isOfficeUser() && $recordRequest->isCancellationConfirmable();
    }

    public function denyCancellation(User $user, RecordRequest $recordRequest): bool
    {
        return $user->isOfficeUser() && $recordRequest->isCancellationDeniable();
    }

    /**
     * Registrar staff and administrators can delete a request, in any status, to declutter
     * the list. Deletion is a soft delete: the record and its audit trail are kept.
     */
    public function delete(User $user, RecordRequest $recordRequest): bool
    {
        return $user->isOfficeUser();
    }

    /**
     * Registrar staff and administrators can recover a request they previously deleted.
     */
    public function restore(User $user, RecordRequest $recordRequest): bool
    {
        return $user->isOfficeUser() && $recordRequest->trashed();
    }
}
