<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecordRequestRequest;
use App\Mail\RecordRequestReceived;
use App\Models\RecordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class RecordRequestController extends Controller
{
    /**
     * Display the request form.
     */
    public function create(): View
    {
        return view('record-requests.create');
    }

    /**
     * Store a new request for student records.
     */
    public function store(StoreRecordRequestRequest $request): RedirectResponse
    {
        $recordRequest = RecordRequest::create([
            ...$request->validated(),
            'reference_no' => RecordRequest::generateReferenceNumber(),
        ]);

        try {
            Mail::to($recordRequest->email)->send(new RecordRequestReceived($recordRequest));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('record-requests.create')
            ->with('reference_no', $recordRequest->reference_no)
            ->with('email', $recordRequest->email);
    }
}
