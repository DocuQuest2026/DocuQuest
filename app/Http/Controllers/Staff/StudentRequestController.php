<?php

namespace App\Http\Controllers\Staff;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\RejectRecordRequestRequest;
use App\Mail\RecordRequestCancellationConfirmed;
use App\Mail\RecordRequestRejected;
use App\Models\RecordRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class StudentRequestController extends Controller
{
    /**
     * Statuses shown as their own filter tab on the requests list, alongside "All".
     *
     * @var array<int, RequestStatus>
     */
    private const FILTERABLE_STATUSES = [
        RequestStatus::Pending,
        RequestStatus::Approved,
        RequestStatus::Released,
        RequestStatus::Rejected,
    ];

    public function __construct(private AuditLogger $audit) {}

    /**
     * List the submitted student record requests in first-come, first-served order (the
     * oldest request at the top), optionally filtered by status. The "Deleted" tab shows
     * soft-deleted requests, and the "Claimed" tab shows released documents that have
     * actually been picked up (a released request can be filtered either way).
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', RecordRequest::class);

        $statusParam = (string) $request->query('status');
        $showingDeleted = $statusParam === 'deleted';
        $showingClaimed = $statusParam === 'claimed';

        if ($showingDeleted) {
            $recordRequests = RecordRequest::onlyTrashed()
                ->latest('deleted_at')
                ->paginate(15)
                ->withQueryString();
            $statusFilter = null;
        } elseif ($showingClaimed) {
            $recordRequests = RecordRequest::where('status', RequestStatus::Released)
                ->whereHas('release', fn ($query) => $query->whereNotNull('claimed_at'))
                ->with('release')
                ->oldest()
                ->orderBy('id')
                ->paginate(15)
                ->withQueryString();
            $statusFilter = null;
        } else {
            $statusFilter = RequestStatus::tryFrom($statusParam);
            $statusFilter = in_array($statusFilter, self::FILTERABLE_STATUSES, true) ? $statusFilter : null;

            $recordRequests = RecordRequest::query()
                ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
                // Once claimed, a request moves out of the "Released" tab and into "Claimed"
                // instead of sitting in both, so the tab only shows documents still awaiting pickup.
                ->when(
                    $statusFilter === RequestStatus::Released,
                    fn ($query) => $query->whereDoesntHave('release', fn ($q) => $q->whereNotNull('claimed_at'))
                )
                ->with('release')
                ->oldest()
                ->orderBy('id')
                ->paginate(15)
                ->withQueryString();
        }

        return view('staff.requests.index', [
            'recordRequests' => $recordRequests,
            'statusFilter' => $statusFilter,
            'showingDeleted' => $showingDeleted,
            'showingClaimed' => $showingClaimed,
            'filterableStatuses' => self::FILTERABLE_STATUSES,
        ]);
    }

    /**
     * List requests the requester has asked to cancel, oldest first, so staff can review
     * and confirm or deny them without digging through the full request list. The
     * "Cancelled" tab shows the record of ones already confirmed, newest first.
     */
    public function cancellations(Request $request): View
    {
        Gate::authorize('viewAny', RecordRequest::class);

        $showingCancelled = $request->query('status') === 'cancelled';

        $recordRequests = $showingCancelled
            ? RecordRequest::where('status', RequestStatus::Cancelled)->latest('cancelled_at')->paginate(15)
            : RecordRequest::where('status', RequestStatus::CancellationRequested)->oldest()->orderBy('id')->paginate(15);

        return view('staff.requests.cancellations', [
            'recordRequests' => $recordRequests->withQueryString(),
            'showingCancelled' => $showingCancelled,
        ]);
    }

    /**
     * Show a single student record request, including a deleted one.
     */
    public function show(RecordRequest $recordRequest): View
    {
        Gate::authorize('view', $recordRequest);

        return view('staff.requests.show', [
            'recordRequest' => $recordRequest->load('release'),
        ]);
    }

    /**
     * Approve a pending request so it can be released.
     */
    public function approve(RecordRequest $recordRequest): RedirectResponse
    {
        Gate::authorize('approve', $recordRequest);

        $recordRequest->approve();
        $this->audit->log(auth()->user(), 'request.approved', $recordRequest);

        return back()->with('status', __('Request approved.'));
    }

    /**
     * Reject a pending request.
     */
    public function reject(RejectRecordRequestRequest $request, RecordRequest $recordRequest): RedirectResponse
    {
        $reason = $request->validated('reason');

        $recordRequest->reject();
        $this->audit->log($request->user(), 'request.rejected', $recordRequest, [
            'reason' => $reason,
        ]);

        try {
            Mail::to($recordRequest->email)->send(new RecordRequestRejected($recordRequest, $reason));
        } catch (Throwable $exception) {
            report($exception);
        }

        return back()->with('status', __('Request rejected.'));
    }

    /**
     * Confirm the requester's cancellation.
     */
    public function confirmCancellation(RecordRequest $recordRequest): RedirectResponse
    {
        Gate::authorize('confirmCancellation', $recordRequest);

        $recordRequest->confirmCancellation();
        $this->audit->log(auth()->user(), 'request.cancellation_confirmed', $recordRequest);

        try {
            Mail::to($recordRequest->email)->send(new RecordRequestCancellationConfirmed($recordRequest));
        } catch (Throwable $exception) {
            report($exception);
        }

        return back()->with('status', __('Cancellation confirmed.'));
    }

    /**
     * Deny the requester's cancellation and keep the request active.
     */
    public function denyCancellation(RecordRequest $recordRequest): RedirectResponse
    {
        Gate::authorize('denyCancellation', $recordRequest);

        $recordRequest->denyCancellation();
        $this->audit->log(auth()->user(), 'request.cancellation_denied', $recordRequest);

        return back()->with('status', __('Cancellation denied; the request is active again.'));
    }

    /**
     * Soft delete the request. The record and its audit trail are kept.
     */
    public function destroy(RecordRequest $recordRequest): RedirectResponse
    {
        Gate::authorize('delete', $recordRequest);

        $this->audit->log(auth()->user(), 'request.deleted', $recordRequest);
        $recordRequest->delete();

        return redirect()->route('requests.index')->with('status', __('Request deleted.'));
    }

    /**
     * Recover a previously deleted request.
     */
    public function restore(RecordRequest $recordRequest): RedirectResponse
    {
        Gate::authorize('restore', $recordRequest);

        $recordRequest->restore();
        $this->audit->log(auth()->user(), 'request.restored', $recordRequest);

        return redirect()->route('requests.show', $recordRequest)->with('status', __('Request restored.'));
    }
}
