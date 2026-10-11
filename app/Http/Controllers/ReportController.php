<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\ReportFilterRequest;
use App\Models\DocumentType;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * The most requests listed in a PDF, so a very wide date range can not exhaust memory.
     */
    private const PDF_REQUEST_LIMIT = 1000;

    public function __construct(private AuditLogger $audit) {}

    /**
     * Summarise the requests submitted in a period, with optional status and document filters.
     */
    public function index(ReportFilterRequest $request): View
    {
        $report = $request->report();

        return view('reports.index', [
            'report' => $report,
            'totals' => $report->totals(),
            'byStatus' => $report->byStatus(),
            'byDocument' => $report->byDocument(),
            'statuses' => RequestStatus::cases(),
            'minDate' => $request->firstDate(),
            'documentTypes' => DocumentType::everyDocument()->sortBy('name'),
            'filters' => [
                'from' => $report->from->toDateString(),
                'to' => $report->to->toDateString(),
                'status' => $report->status?->value,
                'document_type_id' => $report->documentTypeId,
            ],
        ]);
    }

    /**
     * Download the report as a PDF: the summary figures followed by the list of requests.
     */
    public function export(ReportFilterRequest $request): Response
    {
        $report = $request->report();

        $this->audit->log($request->user(), 'report.exported', null, [
            'from' => $report->from->toDateString(),
            'to' => $report->to->toDateString(),
            'status' => $report->status?->value,
            'document_type_id' => $report->documentTypeId,
        ]);

        $requests = $report->requests()->with('release')->latest('record_requests.created_at')->limit(self::PDF_REQUEST_LIMIT + 1)->get();

        return Pdf::loadView('pdf.report', [
            'report' => $report,
            'totals' => $report->totals(),
            'byStatus' => $report->byStatus(),
            'byDocument' => $report->byDocument(),
            'statuses' => RequestStatus::cases(),
            'requests' => $requests->take(self::PDF_REQUEST_LIMIT),
            'truncated' => $requests->count() > self::PDF_REQUEST_LIMIT,
            'requestLimit' => self::PDF_REQUEST_LIMIT,
            'documentName' => $report->documentTypeId ? DocumentType::everyDocument()->get($report->documentTypeId)?->name : null,
            'generatedBy' => $request->user()->name,
        ])
            ->setPaper('a4', 'landscape')
            ->download("docuquest-report-{$report->from->toDateString()}-to-{$report->to->toDateString()}.pdf");
    }
}
