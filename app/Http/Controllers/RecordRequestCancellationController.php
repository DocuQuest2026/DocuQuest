<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\RequestRecordCancellationRequest;
use App\Mail\RecordRequestCancellationSubmitted;
use App\Models\RecordRequest;
use App\Models\User;
use App\Notifications\RecordRequestCancellationRequested;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Throwable;

class RecordRequestCancellationController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Display the cancellation form.
     */
    public function create(): View
    {
        return view('record-requests.cancel');
    }

    /**
     * Submit a cancellation request. Staff must still confirm it before the request is
     * actually cancelled.
     */
    public function store(RequestRecordCancellationRequest $request): RedirectResponse
    {
        $recordRequest = RecordRequest::query()
            ->where('reference_no', mb_strtoupper(trim($request->validated('reference_no'))))
            ->whereRaw('lower(email) = ?', [mb_strtolower(trim($request->validated('email')))])
            ->first();

        if (! $recordRequest) {
            return back()->withInput()->withErrors([
                'reference_no' => __('No pending request was found for that reference number and email.'),
            ]);
        }

        if (! $recordRequest->isCancellable()) {
            return back()->withInput()->withErrors([
                'reference_no' => $this->cancellationBlockedMessage($recordRequest),
            ]);
        }

        $recordRequest->requestCancellation($request->validated('reason'));
        $this->audit->log(null, 'request.cancellation_requested', $recordRequest, [
            'reason' => $request->validated('reason'),
        ]);

        try {
            Mail::to($recordRequest->email)->send(new RecordRequestCancellationSubmitted($recordRequest));
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            Notification::send(
                User::office()->active()->get(),
                new RecordRequestCancellationRequested($recordRequest)
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('record-requests.cancel.create')
            ->with('status', __('Your cancellation request has been sent to the registrar and is awaiting review. We have emailed you a confirmation.'));
    }

    /**
     * Explain why a matched request cannot be cancelled right now.
     */
    private function cancellationBlockedMessage(RecordRequest $recordRequest): string
    {
        if ($recordRequest->hasMissedCancellationWindow()) {
            return __('The cancellation window for this request has passed. Please contact the registrar directly.');
        }

        return match ($recordRequest->status) {
            RequestStatus::CancellationRequested => __('A cancellation is already pending for this request.'),
            RequestStatus::Cancelled => __('This request has already been cancelled.'),
            default => __('This request has already been :status and can no longer be cancelled online.', [
                'status' => mb_strtolower($recordRequest->status->label()),
            ]),
        };
    }
}
