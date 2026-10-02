<?php

namespace App\Http\Controllers\Staff;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\RejectRecordRequestRequest;
use App\Mail\RecordRequestCancellationConfirmed;
use App\Mail\RecordRequestRejected;
use App\Models\RecordRequest;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class StudentRequestController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Show the submitted student record requests. The list itself (first-come, first-served
     * order, status tabs, search, archived and claimed views) lives in the `request-list`
     * Livewire component so it can update as staff type.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', RecordRequest::class);

        return view('staff.requests.index');
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
        $dateColumn = $showingCancelled ? 'cancelled_at' : 'cancellation_requested_at';

        $search = trim((string) $request->query('search'));
        $isDefaultView = ! $request->has('month') && ! $request->has('day');
        $requestedMonth = $request->has('month') ? (string) $request->query('month') : now()->format('Y-m');
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $requestedMonth) ? $requestedMonth : '';
        $firstOfMonth = $month !== '' ? Carbon::createFromFormat('!Y-m', $month) : null;
        $requestedDay = $isDefaultView ? now()->format('d') : (string) $request->query('day');
        $day = $firstOfMonth && preg_match('/^\d{2}$/', $requestedDay) && (int) $requestedDay >= 1 && (int) $requestedDay <= $firstOfMonth->daysInMonth
            ? $requestedDay
            : '';

        $query = RecordRequest::query()
            ->where('status', $showingCancelled ? RequestStatus::Cancelled : RequestStatus::CancellationRequested)
            ->search($search)
            ->when($firstOfMonth !== null, function (Builder $query) use ($firstOfMonth, $day, $dateColumn): void {
                $start = $day !== '' ? $firstOfMonth->copy()->day((int) $day) : $firstOfMonth->copy();
                $end = $day !== '' ? $start->copy()->endOfDay() : $start->copy()->endOfMonth();

                $query->whereBetween($dateColumn, [$start, $end]);
            });

        $recordRequests = $showingCancelled
            ? $query->latest('cancelled_at')->paginate(15)
            : $query->oldest()->orderBy('id')->paginate(15);

        return view('staff.requests.cancellations', [
            'recordRequests' => $recordRequests->withQueryString(),
            'showingCancelled' => $showingCancelled,
            'search' => $search,
            'month' => $month,
            'day' => $day,
            'daysOfMonth' => $firstOfMonth ? range(1, $firstOfMonth->daysInMonth) : [],
        ]);
    }

    /**
     * Show a single student record request, including an archived one.
     */
    public function show(RecordRequest $recordRequest): View
    {
        Gate::authorize('view', $recordRequest);

        return view('staff.requests.show', [
            'recordRequest' => $recordRequest->load('release'),
        ]);
    }

    /**
     * Reveal the requester's full email on the details page for this one page load, and
     * record who looked.
     */
    public function revealEmail(RecordRequest $recordRequest): RedirectResponse
    {
        Gate::authorize('revealEmail', $recordRequest);

        $this->audit->log(auth()->user(), 'request.email_revealed', $recordRequest);

        return redirect()->route('requests.show', $recordRequest)->with('revealed_email', true);
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
     * Archive (soft delete) the request. The record and its audit trail are kept.
     */
    public function destroy(RecordRequest $recordRequest): RedirectResponse
    {
        Gate::authorize('delete', $recordRequest);

        $this->audit->log(auth()->user(), 'request.archived', $recordRequest);
        $recordRequest->delete();

        return redirect()->route('requests.index')->with('status', __('Request archived.'));
    }

    /**
     * Recover a previously archived request.
     */
    public function restore(RecordRequest $recordRequest): RedirectResponse
    {
        Gate::authorize('restore', $recordRequest);

        $recordRequest->restore();
        $this->audit->log(auth()->user(), 'request.restored', $recordRequest);

        return redirect()->route('requests.show', $recordRequest)->with('status', __('Request restored.'));
    }
}
