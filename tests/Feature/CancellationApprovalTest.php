<?php

use App\Enums\RequestStatus;
use App\Mail\RecordRequestCancellationConfirmed;
use App\Models\AuditLog;
use App\Models\RecordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('staff can confirm a requested cancellation', function () {
    $recordRequest = RecordRequest::factory()->cancellationRequested()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.confirm-cancellation', $recordRequest))
        ->assertRedirect();

    $recordRequest->refresh();
    expect($recordRequest->status)->toBe(RequestStatus::Cancelled)
        ->and($recordRequest->cancelled_at)->not->toBeNull();

    expect(AuditLog::where('action', 'request.cancellation_confirmed')->where('subject_id', $recordRequest->id)->exists())->toBeTrue();
});

test('confirming a cancellation emails the requester that it was cancelled', function () {
    Mail::fake();

    $recordRequest = RecordRequest::factory()->cancellationRequested()->create(['email' => 'maria@example.com']);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.confirm-cancellation', $recordRequest));

    Mail::assertSent(RecordRequestCancellationConfirmed::class, fn ($mail) => $mail->hasTo('maria@example.com')
        && $mail->recordRequest->is($recordRequest)
        && $mail->hasSubject('Request cancelled: '.$recordRequest->reference_no));
});

test('denying a cancellation does not email a cancellation-confirmed notice', function () {
    Mail::fake();

    $recordRequest = RecordRequest::factory()->cancellationRequested()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.deny-cancellation', $recordRequest));

    Mail::assertNotSent(RecordRequestCancellationConfirmed::class);
});

test('staff can deny a requested cancellation, keeping the request active', function () {
    $recordRequest = RecordRequest::factory()->cancellationRequested()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.deny-cancellation', $recordRequest))
        ->assertRedirect();

    $recordRequest->refresh();
    expect($recordRequest->status)->toBe(RequestStatus::Pending)
        ->and($recordRequest->cancellation_requested_at)->toBeNull();

    expect(AuditLog::where('action', 'request.cancellation_denied')->where('subject_id', $recordRequest->id)->exists())->toBeTrue();
});

test('a cancellation can only be confirmed or denied while requested', function (string $state) {
    $recordRequest = $state === 'pending' ? RecordRequest::factory()->create() : RecordRequest::factory()->{$state}()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.confirm-cancellation', $recordRequest))->assertForbidden();
    $this->actingAs($staff)->post(route('requests.deny-cancellation', $recordRequest))->assertForbidden();
})->with(['pending', 'approved', 'released', 'rejected', 'cancelled']);

test('students cannot confirm or deny cancellations', function () {
    $recordRequest = RecordRequest::factory()->cancellationRequested()->create();
    $student = User::factory()->create();

    $this->actingAs($student)->post(route('requests.confirm-cancellation', $recordRequest))->assertForbidden();
    $this->actingAs($student)->post(route('requests.deny-cancellation', $recordRequest))->assertForbidden();
});

test('guests are redirected to log in when confirming or denying cancellations', function () {
    $recordRequest = RecordRequest::factory()->cancellationRequested()->create();

    $this->post(route('requests.confirm-cancellation', $recordRequest))->assertRedirect(route('login'));
    $this->post(route('requests.deny-cancellation', $recordRequest))->assertRedirect(route('login'));
});

test('a request awaiting cancellation cannot be approved, rejected, or released', function () {
    $recordRequest = RecordRequest::factory()->cancellationRequested()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.approve', $recordRequest))->assertForbidden();
    $this->actingAs($staff)->post(route('requests.reject', $recordRequest), ['reason' => 'Because'])->assertForbidden();
    $this->actingAs($staff)->get(route('requests.release.create', $recordRequest))->assertForbidden();
});
