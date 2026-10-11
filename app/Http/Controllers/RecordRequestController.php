<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecordRequestRequest;
use App\Mail\RecordRequestReceived;
use App\Models\DocumentType;
use App\Models\RecordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
     * Store a new request for student records. Each document the student chose becomes its
     * own request with its own reference number, so the registrar can approve and release
     * them one at a time. One email lists all of them.
     */
    public function store(StoreRecordRequestRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $chosenDocuments = $validated['documents'];
        unset($validated['documents']);

        /** @var Collection<int, RecordRequest> $recordRequests */
        $recordRequests = DB::transaction(function () use ($validated, $chosenDocuments): Collection {
            $created = new Collection;

            foreach (DocumentType::offered() as $documentType) {
                if (! isset($chosenDocuments[$documentType->key])) {
                    continue;
                }

                $created->push(RecordRequest::create([
                    ...$validated,
                    'document_type_id' => $documentType->id,
                    'fee_per_copy' => $documentType->fee(),
                    'copies' => $chosenDocuments[$documentType->key]['copies'],
                    'reference_no' => RecordRequest::generateReferenceNumber(),
                ]));
            }

            return $created;
        });

        $first = $recordRequests->first();

        try {
            Mail::to($first->email)->send(new RecordRequestReceived($first, $recordRequests));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('record-requests.create')
            ->with('reference_no', $first->reference_no)
            ->with('submitted_requests', $recordRequests->map(fn (RecordRequest $recordRequest): array => [
                'reference_no' => $recordRequest->reference_no,
                'document' => $recordRequest->documentType->label(),
                'copies' => $recordRequest->copies,
            ])->all())
            ->with('email', $first->email);
    }
}
