<?php

namespace App\Mail;

use App\Models\RecordRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class RecordRequestReceived extends Mailable
{
    use SerializesModels;

    /**
     * Every request submitted together, the first one included. A single request is just a
     * list of one.
     *
     * @var Collection<int, RecordRequest>
     */
    public Collection $recordRequests;

    /**
     * @param  Collection<int, RecordRequest>|null  $recordRequests  Everything submitted in one go, when more than one document was requested.
     */
    public function __construct(public RecordRequest $recordRequest, ?Collection $recordRequests = null)
    {
        $this->recordRequests = $recordRequests ?? new Collection([$recordRequest]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $referenceNumbers = $this->recordRequests->pluck('reference_no');

        return new Envelope(
            subject: $referenceNumbers->count() > 1
                ? 'Your reference numbers: '.$referenceNumbers->implode(', ')
                : "Your reference number: {$this->recordRequest->reference_no}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.record-request-received',
            with: [
                'cancelUrl' => route('record-requests.cancel.create', ['reference_no' => $this->recordRequest->reference_no]),
                'cancelUrls' => $this->recordRequests->mapWithKeys(fn (RecordRequest $recordRequest): array => [
                    $recordRequest->reference_no => route('record-requests.cancel.create', ['reference_no' => $recordRequest->reference_no]),
                ]),
                'totalFee' => $this->recordRequests->sum(fn (RecordRequest $recordRequest): int => $recordRequest->document_type->fee() * $recordRequest->copies),
            ],
        );
    }
}
