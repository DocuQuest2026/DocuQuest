<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\RecordRequest;
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

        return view('dashboard', [
            'actionableRequests' => $actionableRequests,
            'actionableCount' => $actionableCount,
            'counts' => $counts,
        ]);
    }
}
