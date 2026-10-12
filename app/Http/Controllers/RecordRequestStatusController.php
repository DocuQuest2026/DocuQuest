<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckRecordRequestStatusRequest;
use App\Models\RecordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecordRequestStatusController extends Controller
{
    /**
     * Show the reference number form, and the status of the last request looked up in this
     * session. It is kept in the session, not flashed, so refreshing the page keeps the status.
     */
    public function create(): View
    {
        $recordRequest = session('status_reference_no')
            ? RecordRequest::where('reference_no', session('status_reference_no'))->first()
            : null;

        return view('record-requests.status', ['recordRequest' => $recordRequest]);
    }

    /**
     * Look up a request by its reference number and show its status.
     */
    public function store(CheckRecordRequestStatusRequest $request): RedirectResponse
    {
        $referenceNo = mb_strtoupper(trim($request->validated('reference_no')));

        if (! RecordRequest::where('reference_no', $referenceNo)->exists()) {
            return back()->withInput()->withErrors([
                'reference_no' => __('No request was found for that reference number.'),
            ]);
        }

        $request->session()->put('status_reference_no', $referenceNo);

        return redirect()->route('record-requests.status.create');
    }
}
