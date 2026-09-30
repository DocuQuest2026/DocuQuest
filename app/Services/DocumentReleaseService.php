<?php

namespace App\Services;

use App\Mail\RecordRequestReleased;
use App\Models\DocumentRelease;
use App\Models\RecordRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DocumentReleaseService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Release a document to the representative who claims it: record who claimed it,
     * move the request to Released, and issue a verifiable PDF.
     *
     * @param  array<string, mixed>  $data
     */
    public function release(RecordRequest $recordRequest, User $releasedBy, array $data): DocumentRelease
    {
        if (! $recordRequest->isReleasable()) {
            throw new RuntimeException('Only an approved request can be released.');
        }

        $documentRelease = DB::transaction(function () use ($recordRequest, $releasedBy, $data) {
            $documentRelease = DocumentRelease::create([
                'record_request_id' => $recordRequest->id,
                'released_by' => $releasedBy->id,
                'released_at' => now(),
                'claim_available_at' => $data['claim_available_at'],
                'representative_name' => $data['representative_name'],
                'verification_token' => DocumentRelease::generateVerificationToken(),
            ]);

            $recordRequest->markReleased();

            $pdfPath = $this->storeReleasePdf($recordRequest, $documentRelease);
            $documentRelease->update(['pdf_path' => $pdfPath]);

            $this->audit->log($releasedBy, 'request.released', $recordRequest, [
                'verification_token' => $documentRelease->verification_token,
                'representative_name' => $documentRelease->representative_name,
                'claim_available_at' => $documentRelease->claim_available_at->toIso8601String(),
            ]);

            return $documentRelease;
        });

        try {
            Mail::to($recordRequest->email)->send(new RecordRequestReleased($recordRequest, $documentRelease));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $documentRelease;
    }

    /**
     * Render and store the released document as a PDF carrying a QR code that resolves
     * to the public verification page.
     */
    private function storeReleasePdf(RecordRequest $recordRequest, DocumentRelease $documentRelease): string
    {
        $verificationUrl = $documentRelease->verificationUrl();
        $qrDataUri = (new Builder)->build(data: $verificationUrl)->getDataUri();

        $pdf = Pdf::loadView('pdf.released-document', [
            'recordRequest' => $recordRequest,
            'release' => $documentRelease,
            'qrDataUri' => $qrDataUri,
            'verificationUrl' => $verificationUrl,
        ]);

        $path = "releases/{$recordRequest->id}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
