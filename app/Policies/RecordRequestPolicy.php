<?php

namespace App\Policies;

use App\Enums\RequestStatus;
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

    /**
     * Registrar staff and administrators can reveal the requester's full email address. The
     * address is masked by default, and every reveal is audited.
     */
    public function revealEmail(User $user, RecordRequest $recordRequest): bool
    {
        return $user->isOfficeUser();
    }

    /**
     * Registrar staff and administrators can record that a released document was actually
     * picked up.
     */
    public function claim(User $user, RecordRequest $recordRequest): bool
    {
        return $user->isOfficeUser() && $recordRequest->isClaimable();
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
     * Registrar staff and administrators can archive a request to declutter
     * the list, except while it is still pending or approved and needs action. Archiving is a
     * soft delete: the record and its audit trail are kept.
     */
    public function delete(User $user, RecordRequest $recordRequest): bool
    {
        return $user->isOfficeUser()
            && ! in_array($recordRequest->status, [RequestStatus::Pending, RequestStatus::Approved], true);
    }

    /**
     * Registrar staff and administrators can recover a request they previously archived.
     */
    public function restore(User $user, RecordRequest $recordRequest): bool
    {
        return $user->isOfficeUser() && $recordRequest->trashed();
    }
}
