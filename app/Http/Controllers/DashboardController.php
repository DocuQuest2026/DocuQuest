<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\RecordRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Requests in these statuses need a staff member to do something next.
     *
     * @var array<int, RequestStatus>
     */
    private const ACTIONABLE_STATUSES = [
        RequestStatus::Pending,
        RequestStatus::CancellationRequested,
        RequestStatus::Approved,
    ];

    public function index(): View
    {
        if (! Gate::allows('viewAny', RecordRequest::class)) {
            return view('dashboard');
        }

        // The counts are cached and cleared whenever a request changes, so showing them costs
        // no extra database round trip.
        $counts = RecordRequest::badgeCounts();

        $actionableCount = array_sum(array_map(
            fn (RequestStatus $status): int => $counts[$status->value] ?? 0,
            self::ACTIONABLE_STATUSES,
        ));

        $actionableRequests = RecordRequest::query()
            ->whereIn('status', self::ACTIONABLE_STATUSES)
            ->oldest()
            ->limit(10)
            ->get();

        // Cached under the same version as the badge counts, so any request or release change
        // retires it, and the date in the key rolls it over at midnight.
        $today = Cache::remember(
            'dashboard.today.'.RecordRequest::cacheVersion().'.'.now()->toDateString(),
            300,
            fn (): array => $this->todayFigures(),
        );

        return view('dashboard', [
            'actionableRequests' => $actionableRequests,
            'today' => $today,
            'actionableCount' => $actionableCount,
            'counts' => $counts,
        ]);
    }

    /**
     * What happened today: requests received, documents released and urgent requests waiting.
     *
     * @return array{received: int, released: int, urgent: int}
     */
    private function todayFigures(): array
    {
        $figures = RecordRequest::query()
            ->toBase()
            ->selectRaw(
                'count(*) filter (where created_at >= ?) as received,
                count(*) filter (where is_urgent and status in (?, ?)) as urgent',
                [now()->startOfDay(), RequestStatus::Pending->value, RequestStatus::Approved->value]
            )
            ->selectRaw(
                '(select count(*) from document_releases join record_requests as released_requests on released_requests.id = document_releases.record_request_id where document_releases.released_at >= ? and released_requests.deleted_at is null) as released',
                [now()->startOfDay()]
            )
            ->whereNull('deleted_at')
            ->first();

        return [
            'received' => (int) $figures->received,
            'released' => (int) $figures->released,
            'urgent' => (int) $figures->urgent,
        ];
    }
}
