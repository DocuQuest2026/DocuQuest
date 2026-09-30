<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreDocumentClaimRequest;
use App\Http\Requests\Staff\StoreDocumentReleaseRequest;
use App\Models\RecordRequest;
use App\Services\AuditLogger;
use App\Services\DocumentReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DocumentReleaseController extends Controller
{
    public function __construct(private DocumentReleaseService $releases, private AuditLogger $audit) {}

    /**
     * Show the form for capturing the representative's details.
     */
    public function create(RecordRequest $recordRequest): View
    {
        Gate::authorize('release', $recordRequest);

        return view('staff.requests.release', ['recordRequest' => $recordRequest]);
    }

    /**
     * Release the document to the representative.
     */
    public function store(StoreDocumentReleaseRequest $request, RecordRequest $recordRequest): RedirectResponse
    {
        $this->releases->release($recordRequest, $request->user(), $request->validated());

        return redirect()->route('requests.show', $recordRequest)->with('status', __('Document released.'));
    }

    /**
     * Record that the requester or their representative actually picked up the document.
     * The date and time default to the moment the button is pressed, but staff can set a
     * different one (e.g. logging a pickup that actually happened earlier).
     */
    public function claim(StoreDocumentClaimRequest $request, RecordRequest $recordRequest): RedirectResponse
    {
        $recordRequest->release->update(['claimed_at' => $request->validated('claimed_at')]);
        $this->audit->log(auth()->user(), 'request.claimed', $recordRequest, [
            'claimed_at' => $recordRequest->release->claimed_at->toIso8601String(),
        ]);

        return back()->with('status', __('Document marked as claimed.'));
    }
}
