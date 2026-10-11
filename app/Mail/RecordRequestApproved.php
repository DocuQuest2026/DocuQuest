<?php

namespace App\Mail;

use App\Models\RecordRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordRequestApproved extends Mailable
{
    use SerializesModels;

    public function __construct(public RecordRequest $recordRequest) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Request approved: {$this->recordRequest->reference_no}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.record-request-approved',
        );
    }
}
