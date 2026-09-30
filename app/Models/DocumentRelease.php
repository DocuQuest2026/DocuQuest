<?php

namespace App\Models;

use Database\Factories\DocumentReleaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

#[Fillable([
    'record_request_id',
    'released_by',
    'released_at',
    'claim_available_at',
    'claimed_at',
    'representative_name',
    'verification_token',
    'pdf_path',
])]
class DocumentRelease extends Model
{
    /** @use HasFactory<DocumentReleaseFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'released_at' => 'datetime',
            'claim_available_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    /**
     * Whether the requester or their representative has actually picked up the document.
     */
    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }

    /**
     * @return BelongsTo<RecordRequest, $this>
     */
    public function recordRequest(): BelongsTo
    {
        return $this->belongsTo(RecordRequest::class);
    }

    /**
     * The staff member is looked up including soft-deleted accounts: a foreign key stops
     * this user from ever being permanently deleted while this release references them, so
     * they always exist, but may have since been (soft) deleted from the staff list.
     *
     * @return BelongsTo<User, $this>
     */
    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by')->withTrashed();
    }

    /**
     * Bind the verification token in URLs so internal ids are never exposed to the public.
     */
    public function getRouteKeyName(): string
    {
        return 'verification_token';
    }

    /**
     * A signed link that lets anyone holding the released document confirm it is genuine.
     */
    public function verificationUrl(): string
    {
        return URL::temporarySignedRoute(
            'document-verification.show',
            now()->addYears(config('school.document_verification_ttl_years')),
            $this
        );
    }

    /**
     * Generate a verification token that no other release is using.
     */
    public static function generateVerificationToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where('verification_token', $token)->exists());

        return $token;
    }
}
