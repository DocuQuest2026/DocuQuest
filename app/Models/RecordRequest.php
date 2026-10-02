<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\EnrolmentStatus;
use App\Enums\RequestStatus;
use App\Enums\ValidIdType;
use Database\Factories\RecordRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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
    'designated_representative_id_type',
])]
class RecordRequest extends Model
{
    /** @use HasFactory<RecordRequestFactory> */
    use HasFactory, SoftDeletes;

    private const BADGE_COUNTS_CACHE_PREFIX = 'navigation.request-counts';

    private const BADGE_COUNTS_VERSION_KEY = 'navigation.request-counts.version';

    private const BADGE_COUNTS_CACHE_SECONDS = 60;

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
            'designated_representative_id_type' => ValidIdType::class,
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
     * How the requester sees the status: the badge label and its color classes. A released
     * document that was already picked up shows as claimed.
     *
     * @return array{label: string, classes: string}
     */
    public function requesterStatus(): array
    {
        return match (true) {
            $this->status === RequestStatus::Released && $this->release?->isClaimed() => ['label' => __('Claimed'), 'classes' => 'bg-green-100 text-green-800'],
            $this->status === RequestStatus::Pending => ['label' => __('Being processed'), 'classes' => 'bg-yellow-100 text-yellow-800'],
            $this->status === RequestStatus::Approved => ['label' => __('Approved — being prepared'), 'classes' => 'bg-blue-100 text-blue-800'],
            $this->status === RequestStatus::Released => ['label' => __('Ready to claim'), 'classes' => 'bg-green-100 text-green-800'],
            $this->status === RequestStatus::Rejected => ['label' => __('Rejected'), 'classes' => 'bg-red-100 text-red-800'],
            $this->status === RequestStatus::CancellationRequested => ['label' => __('Cancellation requested'), 'classes' => 'bg-orange-100 text-orange-800'],
            default => ['label' => __('Cancelled'), 'classes' => 'bg-gray-200 text-gray-700'],
        };
    }

    /**
     * How staff see the status: the badge label and its color classes. A released document that
     * has been picked up shows as claimed.
     *
     * @return array{label: string, classes: string}
     */
    public function staffStatus(): array
    {
        return match (true) {
            $this->status === RequestStatus::Released && $this->release?->isClaimed() => ['label' => __('Claimed'), 'classes' => 'bg-teal-100 text-teal-800'],
            $this->status === RequestStatus::Pending => ['label' => $this->status->label(), 'classes' => 'bg-amber-100 text-amber-800'],
            $this->status === RequestStatus::Approved => ['label' => $this->status->label(), 'classes' => 'bg-blue-100 text-blue-800'],
            $this->status === RequestStatus::Released => ['label' => $this->status->label(), 'classes' => 'bg-green-100 text-green-800'],
            $this->status === RequestStatus::Rejected => ['label' => $this->status->label(), 'classes' => 'bg-red-100 text-red-800'],
            $this->status === RequestStatus::CancellationRequested => ['label' => $this->status->label(), 'classes' => 'bg-orange-100 text-orange-800'],
            default => ['label' => $this->status->label(), 'classes' => 'bg-gray-200 text-gray-700'],
        };
    }

    /**
     * The fee in pesos for all copies of the document: the per-copy fee times the copies.
     */
    public function totalFee(): int
    {
        return $this->document_type->fee() * $this->copies;
    }

    /**
     * The total fee formatted for display, e.g. "₱300.00".
     */
    public function formattedTotalFee(): string
    {
        return '₱'.number_format($this->totalFee(), 2);
    }

    /**
     * The cancellation window as a phrase for messages: "within a day" or "within 3 days".
     */
    public static function cancellationWindowPhrase(): string
    {
        return trans_choice('within a day|within :count days', (int) config('school.cancellation_window_days'));
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
    public function requestCancellation(string $reason): void
    {
        $this->forceFill([
            'status' => RequestStatus::CancellationRequested,
            'cancellation_requested_at' => now(),
            'cancellation_reason' => $reason,
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
     * Only a released document that has not already been picked up can be marked claimed.
     */
    public function isClaimable(): bool
    {
        return $this->status === RequestStatus::Released
            && $this->release !== null
            && ! $this->release->isClaimed();
    }

    /**
     * Bind the reference number in URLs so internal ids are never exposed to the public.
     */
    public function getRouteKeyName(): string
    {
        return 'reference_no';
    }

    /**
     * The requester's email with most of the address hidden (e.g. j***@gmail.com). The
     * mask has a fixed length so it does not reveal how long the address is.
     */
    public function maskedEmail(): string
    {
        [$local, $domain] = array_pad(explode('@', $this->email, 2), 2, '');

        return mb_substr($local, 0, 1).'***@'.$domain;
    }

    /**
     * Match requests whose reference number, student number or name contains every word
     * of the search term, so "juan cruz" finds "Juan Dela Cruz". Results whose first name
     * starts with the first word typed come first, then last-name, reference and student
     * number prefixes, then everything else; later ordering applies within each group.
     *
     * @param  Builder<RecordRequest>  $query
     * @return Builder<RecordRequest>
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): Builder
    {
        $words = preg_split('/\s+/', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY);

        if ($words !== []) {
            $prefix = mb_strtolower(addcslashes($words[0], '\\%_')).'%';

            $query->orderByRaw(
                'CASE WHEN LOWER(first_name) LIKE ? THEN 0'
                .' WHEN LOWER(last_name) LIKE ? OR LOWER(reference_no) LIKE ? OR LOWER(student_no) LIKE ? THEN 1'
                .' ELSE 2 END',
                [$prefix, $prefix, $prefix, $prefix]
            );
        }

        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '\\%_').'%';

            $query->where(function (Builder $q) use ($like): void {
                $q->whereLike('reference_no', $like)
                    ->orWhereLike('student_no', $like)
                    ->orWhereLike('first_name', $like)
                    ->orWhereLike('middle_name', $like)
                    ->orWhereLike('last_name', $like);
            });
        }

        return $query;
    }

    /**
     * How many requests need attention in each status, for the notification badges: cancellation
     * requests, pending, approved, and released documents still waiting to be picked up (claimed
     * ones are not counted). Pass a month (and optionally a day) to count only the requests
     * submitted then, as the staff list's date filter does.
     *
     * Every query is a round trip to the remote database, so each result is cached briefly under
     * the current cache version, and forgetBadgeCounts() retires all of them at once as soon as
     * a request or release changes.
     *
     * @return array<string, int> Counts keyed by status value; a status with none may be absent.
     */
    public static function badgeCounts(?string $month = null, ?string $day = null): array
    {
        $version = static::cacheVersion();

        return Cache::remember(
            self::BADGE_COUNTS_CACHE_PREFIX.".{$version}.{$month}.{$day}",
            self::BADGE_COUNTS_CACHE_SECONDS,
            fn (): array => static::query()
                ->leftJoin('document_releases', 'document_releases.record_request_id', '=', 'record_requests.id')
                ->whereIn('record_requests.status', [
                    RequestStatus::CancellationRequested,
                    RequestStatus::Pending,
                    RequestStatus::Approved,
                    RequestStatus::Released,
                ])
                ->submittedIn($month, $day)
                ->selectRaw(
                    'record_requests.status as status, sum(case when record_requests.status = ? and document_releases.claimed_at is not null then 0 else 1 end) as total',
                    [RequestStatus::Released->value]
                )
                ->groupBy('record_requests.status')
                ->pluck('total', 'status')
                ->map(fn ($total): int => (int) $total)
                ->all()
        );
    }

    /**
     * A number that changes whenever any request or release changes (see forgetBadgeCounts()).
     * Anything cached under a key containing it is automatically retired along with the badges.
     */
    public static function cacheVersion(): int
    {
        return (int) Cache::get(self::BADGE_COUNTS_VERSION_KEY, 1);
    }

    public static function forgetBadgeCounts(): void
    {
        Cache::add(self::BADGE_COUNTS_VERSION_KEY, 1);
        Cache::increment(self::BADGE_COUNTS_VERSION_KEY);
    }

    /**
     * Only requests submitted during the given month ("Y-m", as sent by a month picker), or on
     * one exact day of it when a two-digit day is also given. An empty, malformed or impossible
     * value (such as day 31 of February) leaves that part of the query unfiltered.
     *
     * @param  Builder<RecordRequest>  $query
     * @return Builder<RecordRequest>
     */
    #[Scope]
    protected function submittedIn(Builder $query, ?string $month, ?string $day = null): Builder
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $month)) {
            return $query;
        }

        $start = Carbon::createFromFormat('!Y-m', $month);
        $column = $query->getModel()->qualifyColumn('created_at');

        if (preg_match('/^\d{2}$/', (string) $day) && (int) $day >= 1 && (int) $day <= $start->daysInMonth) {
            $start = $start->copy()->day((int) $day);

            return $query->whereBetween($column, [$start, $start->copy()->endOfDay()]);
        }

        return $query->whereBetween($column, [$start, $start->copy()->endOfMonth()]);
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
