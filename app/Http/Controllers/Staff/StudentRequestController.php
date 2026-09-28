<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\RejectRecordRequestRequest;
use App\Mail\RecordRequestRejected;
use App\Models\RecordRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class StudentRequestController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * List the submitted student record requests, newest first.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', RecordRequest::class);

        return view('staff.requests.index', [
            'recordRequests' => RecordRequest::latest()->orderByDesc('id')->paginate(15),
        ]);
    }

    /**
     * Show a single student record request.
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
}
