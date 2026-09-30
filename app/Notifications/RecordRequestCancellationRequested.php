<?php

namespace App\Notifications;

use App\Models\RecordRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecordRequestCancellationRequested extends Notification
{
    use Queueable;

    public function __construct(public RecordRequest $recordRequest) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Cancellation requested: {$this->recordRequest->reference_no}")
            ->line("{$this->recordRequest->fullName()} has asked to cancel request {$this->recordRequest->reference_no}.")
            ->line("Document: {$this->recordRequest->document_type->label()}")
            ->action('Review request', route('requests.show', $this->recordRequest))
            ->line('Confirm or deny the cancellation from the request page.');
    }

    /**
     * Get the array representation of the notification, stored for the in-app notification list.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reference_no' => $this->recordRequest->reference_no,
            'student_name' => $this->recordRequest->fullName(),
            'document_type' => $this->recordRequest->document_type->label(),
            'url' => route('requests.show', $this->recordRequest),
        ];
    }
}
