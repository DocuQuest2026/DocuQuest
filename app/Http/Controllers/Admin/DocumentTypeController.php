<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDocumentTypeRequest;
use App\Http\Requests\Admin\UpdateDocumentTypeRequest;
use App\Models\DocumentType;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DocumentTypeController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * List every document, offered or not, with its fee and processing time.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', DocumentType::class);

        return view('admin.document-types.index', [
            'documentTypes' => DocumentType::query()->withCount('recordRequests')->orderBy('name')->orderBy('id')->get(),
        ]);
    }

    /**
     * Show the form for adding a new document.
     */
    public function create(): View
    {
        Gate::authorize('create', DocumentType::class);

        return view('admin.document-types.create');
    }

    /**
     * Add a document requesters can choose from.
     */
    public function store(StoreDocumentTypeRequest $request): RedirectResponse
    {
        $documentType = DocumentType::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->audit->log($request->user(), 'document_type.created', $documentType, $documentType->only(['name', 'fee', 'processing_days', 'is_active']));

        return redirect()->route('admin.document-types.index')->with('status', __('Document added.'));
    }

    /**
     * Show the form for editing a document's name, fee and processing time.
     */
    public function edit(DocumentType $documentType): View
    {
        Gate::authorize('update', DocumentType::class);

        return view('admin.document-types.edit', ['documentType' => $documentType]);
    }

    /**
     * Update a document. Requests already submitted keep the fee they were submitted with;
     * the new fee and processing time apply to requests from now on.
     */
    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType): RedirectResponse
    {
        $documentType->fill([...$request->validated(), 'is_active' => $request->boolean('is_active')]);

        $changes = collect($documentType->getDirty())
            ->mapWithKeys(fn (mixed $new, string $attribute): array => [$attribute => ['from' => $documentType->getOriginal($attribute), 'to' => $new]])
            ->all();

        $documentType->save();

        if ($changes !== []) {
            $this->audit->log($request->user(), 'document_type.updated', $documentType, $changes);
        }

        return redirect()->route('admin.document-types.index')->with('status', __('Document updated.'));
    }
}
