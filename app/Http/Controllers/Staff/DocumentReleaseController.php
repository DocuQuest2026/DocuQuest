<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreDocumentReleaseRequest;
use App\Models\RecordRequest;
use App\Services\DocumentReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DocumentReleaseController extends Controller
{
    public function __construct(private DocumentReleaseService $releases) {}

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
}
