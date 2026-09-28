<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\RecordRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentRequestController extends Controller
{
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

        return view('staff.requests.show', ['recordRequest' => $recordRequest]);
    }
}
