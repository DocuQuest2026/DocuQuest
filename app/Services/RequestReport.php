<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\RecordRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RequestReport
{
    /** @var Collection<string, object>|null */
    private ?Collection $statusRows = null;

    /**
     * @param  Carbon  $from  First day included in the report.
     * @param  Carbon  $to  Last day included in the report.
     */
    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly ?RequestStatus $status = null,
        public readonly ?int $documentTypeId = null,
    ) {}

    /**
     * The requests the report covers: submitted within the date range and matching the filters.
     * Archived requests are included, since archiving only tidies the staff list.
     *
     * @return Builder<RecordRequest>
     */
    public function requests(): Builder
    {
        return RecordRequest::withTrashed()
            ->whereBetween('record_requests.created_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()])
            ->when($this->status, fn (Builder $query, RequestStatus $status) => $query->where('record_requests.status', $status))
            ->when($this->documentTypeId, fn (Builder $query, int $id) => $query->where('record_requests.document_type_id', $id));
    }

    /**
     * One grouped query that feeds both the headline numbers and the per-status counts, so the
     * report needs a single round trip to the database for them instead of two.
     *
     * @return Collection<string, object{total: int, urgent: int, copies: int, fees: int}> Keyed by status value.
     */
    private function statusRows(): Collection
    {
        return $this->statusRows ??= $this->requests()
            ->selectRaw('record_requests.status as status')
            ->selectRaw('count(*) as total')
            ->selectRaw('coalesce(sum(case when is_urgent then 1 else 0 end), 0) as urgent')
            ->selectRaw('coalesce(sum(copies), 0) as copies')
            ->selectRaw('coalesce(sum(fee_per_copy * copies), 0) as fees')
            ->groupBy('record_requests.status')
            ->toBase()
            ->get()
            ->mapWithKeys(fn (object $row): array => [$row->status => (object) [
                'total' => (int) $row->total,
                'urgent' => (int) $row->urgent,
                'copies' => (int) $row->copies,
                'fees' => (int) $row->fees,
            ]]);
    }

    /**
     * Headline numbers for the period.
     *
     * @return array{total: int, urgent: int, copies: int, fees: int, collectedFees: int, averageProcessingDays: ?float}
     */
    public function totals(): array
    {
        $rows = $this->statusRows();

        return [
            'total' => $rows->sum('total'),
            'urgent' => $rows->sum('urgent'),
            'copies' => $rows->sum('copies'),
            'fees' => $rows->sum('fees'),
            'collectedFees' => (int) ($rows[RequestStatus::Released->value]->fees ?? 0),
            'averageProcessingDays' => $this->averageProcessingDays(),
        ];
    }

    /**
     * How many requests are in each status, every status included.
     *
     * @return array<string, int> Counts keyed by status value.
     */
    public function byStatus(): array
    {
        $rows = $this->statusRows();

        return collect(RequestStatus::cases())
            ->mapWithKeys(fn (RequestStatus $status): array => [$status->value => (int) ($rows[$status->value]->total ?? 0)])
            ->all();
    }

    /**
     * Requests, copies and fees for each document.
     *
     * @return Collection<int, object{name: string, requests: int, copies: int, fees: int}>
     */
    public function byDocument(): Collection
    {
        return $this->requests()
            ->join('document_types', 'document_types.id', '=', 'record_requests.document_type_id')
            ->selectRaw('document_types.name as name, count(*) as requests, sum(copies) as copies, sum(fee_per_copy * copies) as fees')
            ->groupBy('document_types.id', 'document_types.name')
            ->orderByDesc('requests')
            ->orderBy('document_types.name')
            ->toBase()
            ->get()
            ->map(fn (object $row): object => (object) [
                'name' => $row->name,
                'requests' => (int) $row->requests,
                'copies' => (int) $row->copies,
                'fees' => (int) $row->fees,
            ]);
    }

    /**
     * The average days from submission to release, over the released requests in the report.
     * Null when none were released.
     */
    private function averageProcessingDays(): ?float
    {
        $seconds = $this->requests()
            ->join('document_releases', 'document_releases.record_request_id', '=', 'record_requests.id')
            ->where('record_requests.status', RequestStatus::Released)
            ->toBase()
            ->avg(DB::raw('extract(epoch from (document_releases.released_at - record_requests.created_at))'));

        return $seconds === null ? null : round($seconds / 86400, 1);
    }
}
