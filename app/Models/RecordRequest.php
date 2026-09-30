<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\EnrolmentStatus;
use App\Enums\RequestStatus;
use Database\Factories\RecordRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

#[Fillable([
    'reference_no',
    'student_no',
    'first_name',
    'middle_name',
    'last_name',
    'course',
    'enrolment_status',
    'email',
    'contact_no',
    'document_type',
    'copies',
    'purpose',
    'designated_representative_name',
])]
class RecordRequest extends Model
{
    /** @use HasFactory<RecordRequestFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enrolment_status' => EnrolmentStatus::class,
            'document_type' => DocumentType::class,
            'copies' => 'integer',
            'status' => RequestStatus::class,
            'cancelled_at' => 'datetime',
            'cancellation_requested_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return HasOne<DocumentRelease, $this>
     */
    public function release(): HasOne
    {
        return $this->hasOne(DocumentRelease::class);
    }

    /**
     * Only requests the registrar has not acted on, and that are still within the
     * cancellation window, can have cancellation requested.
     */
    public function isCancellable(): bool
    {
        return $this->status === RequestStatus::Pending && $this->isWithinCancellationWindow();
    }

    /**
     * Whether the requester is still within the window to ask for a cancellation.
     */
    public function isWithinCancellationWindow(): bool
    {
        return $this->created_at
            ->addDays(config('school.cancellation_window_days'))
            ->isFuture();
    }

    /**
     * A pending request that has aged past the cancellation window and can no longer
     * be cancelled by the requester.
     */
    public function hasMissedCancellationWindow(): bool
    {
        return $this->status === RequestStatus::Pending && ! $this->isWithinCancellationWindow();
    }

    /**
     * The requester has asked to withdraw the request; staff must confirm it before it
     * is actually cancelled.
     */
    public function requestCancellation(): void
    {
        $this->forceFill([
            'status' => RequestStatus::CancellationRequested,
            'cancellation_requested_at' => now(),
        ])->save();
    }

    /**
     * Only a request the requester has asked to cancel can be confirmed or denied.
     */
    public function isCancellationConfirmable(): bool
    {
        return $this->status === RequestStatus::CancellationRequested;
    }

    public function isCancellationDeniable(): bool
    {
        return $this->status === RequestStatus::CancellationRequested;
    }

    public function confirmCancellation(): void
    {
        $this->forceFill([
            'status' => RequestStatus::Cancelled,
            'cancelled_at' => now(),
        ])->save();
    }

    /**
     * Keep the request active; the requester's cancellation was not confirmed.
     */
    public function denyCancellation(): void
    {
        $this->forceFill([
            'status' => RequestStatus::Pending,
            'cancellation_requested_at' => null,
        ])->save();
    }

    /**
     * Only a pending request can be approved.
     */
    public function isApprovable(): bool
    {
        return $this->status === RequestStatus::Pending;
    }

    /**
     * Only a pending request can be rejected.
     */
    public function isRejectable(): bool
    {
        return $this->status === RequestStatus::Pending;
    }

    /**
     * Only an approved request can be released to a representative.
     */
    public function isReleasable(): bool
    {
        return $this->status === RequestStatus::Approved;
    }

    public function approve(): void
    {
        $this->forceFill(['status' => RequestStatus::Approved])->save();
    }

    public function reject(): void
    {
        $this->forceFill(['status' => RequestStatus::Rejected])->save();
    }

    public function markReleased(): void
    {
        $this->forceFill(['status' => RequestStatus::Released])->save();
    }

    /**
     * A signed link, valid for two weeks, that lets the requester open the cancellation
     * page. The link itself outlives the shorter cancellation window (see
     * isWithinCancellationWindow()) so that a request made past the window shows a clear
     * "window has passed" message instead of a bare invalid-link error.
     */
    public function cancellationUrl(): string
    {
        return URL::temporarySignedRoute('record-requests.cancel.show', now()->addDays(14), $this);
    }

    /**
     * Bind the reference number in URLs so internal ids are never exposed to the public.
     */
    public function getRouteKeyName(): string
    {
        return 'reference_no';
    }

    /**
     * The requester's full name as it should appear on the document.
     */
    public function fullName(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name])->filter()->implode(' ');
    }

    /**
     * Generate a reference number that no other request is using.
     */
    public static function generateReferenceNumber(): string
    {
        do {
            $referenceNumber = 'REQ-'.Str::upper(Str::random(8));
        } while (static::where('reference_no', $referenceNumber)->exists());

        return $referenceNumber;
    }
}
