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

        $actionableCount = RecordRequest::query()->whereIn('status', self::ACTIONABLE_STATUSES)->count();

        $actionableRequests = RecordRequest::query()
            ->whereIn('status', self::ACTIONABLE_STATUSES)
            ->oldest()
            ->limit(10)
            ->get();

        return view('dashboard', [
            'actionableRequests' => $actionableRequests,
            'actionableCount' => $actionableCount,
        ]);
    }
}
