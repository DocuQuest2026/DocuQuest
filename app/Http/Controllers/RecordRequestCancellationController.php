<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendCancellationLinkRequest;
use App\Mail\RecordRequestReceived;
use App\Models\RecordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RecordRequestCancellationController extends Controller
{
    /**
     * Display the form for requesting a new cancellation link.
     */
    public function create(): View
    {
        return view('record-requests.cancel-link');
    }

    /**
     * Email the cancellation link to the address on file. The response is identical whether or
     * not a request matched, so reference numbers and emails cannot be probed.
     */
    public function sendLink(SendCancellationLinkRequest $request): RedirectResponse
    {
        $recordRequest = RecordRequest::query()
            ->where('reference_no', mb_strtoupper(trim($request->validated('reference_no'))))
            ->whereRaw('lower(email) = ?', [mb_strtolower(trim($request->validated('email')))])
            ->first();

        if ($recordRequest?->isCancellable()) {
            Mail::to($recordRequest->email)->send(new RecordRequestReceived($recordRequest));
        }

        return redirect()
            ->route('record-requests.cancel.create')
            ->with('status', __('If those details match a pending request, we have emailed a cancellation link to the address on file.'));
    }

    /**
     * Ask the requester to confirm the cancellation. Opening the link never cancels on its own.
     */
    public function show(RecordRequest $recordRequest): View
    {
        return view('record-requests.cancel', ['recordRequest' => $recordRequest]);
    }

    /**
     * Cancel the request.
     */
    public function store(Request $request, RecordRequest $recordRequest): RedirectResponse
    {
        if ($recordRequest->isCancellable()) {
            $recordRequest->cancel();
        }

        return redirect($request->fullUrl());
    }
}
