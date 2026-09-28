<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\EnrolmentStatus;
use App\Enums\RequestStatus;
use Database\Factories\RecordRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
])]
class RecordRequest extends Model
{
    /** @use HasFactory<RecordRequestFactory> */
    use HasFactory;

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
        ];
    }

    /**
     * Only requests the registrar has not acted on can be withdrawn.
     */
    public function isCancellable(): bool
    {
        return $this->status === RequestStatus::Pending;
    }

    public function cancel(): void
    {
        $this->forceFill([
            'status' => RequestStatus::Cancelled,
            'cancelled_at' => now(),
        ])->save();
    }

    /**
     * A signed link, valid for two weeks, that lets the requester cancel this request.
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
