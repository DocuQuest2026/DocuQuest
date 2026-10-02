<?php

use App\Enums\RequestStatus;
use App\Mail\RecordRequestRejected;
use App\Models\AuditLog;
use App\Models\RecordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('staff can approve a pending request', function () {
    $recordRequest = RecordRequest::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.approve', $recordRequest))
        ->assertRedirect();

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::Approved);
    expect(AuditLog::where('action', 'request.approved')->where('subject_id', $recordRequest->id)->exists())->toBeTrue();
});

test('staff can reject a pending request with a reason', function () {
    $recordRequest = RecordRequest::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.reject', $recordRequest), ['reason' => 'Incomplete details.'])
        ->assertRedirect();

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::Rejected);

    $log = AuditLog::where('action', 'request.rejected')->where('subject_id', $recordRequest->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->metadata['reason'])->toBe('Incomplete details.');
});

test('rejecting emails the requester the reason', function () {
    Mail::fake();

    $recordRequest = RecordRequest::factory()->create(['email' => 'maria@example.com']);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.reject', $recordRequest), ['reason' => 'Incomplete details.']);

    Mail::assertSent(RecordRequestRejected::class, fn ($mail) => $mail->hasTo('maria@example.com')
        && $mail->hasSubject('Request rejected: '.$recordRequest->reference_no)
        && str_contains($mail->render(), 'Incomplete details.'));
});

test('the request page shows a confirmation after rejecting', function () {
    $recordRequest = RecordRequest::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->from(route('requests.show', $recordRequest))
        ->post(route('requests.reject', $recordRequest), ['reason' => 'Incomplete details.'])
        ->assertRedirect(route('requests.show', $recordRequest))
        ->assertSessionHas('status', 'Request rejected.');

    $this->actingAs($staff)->get(route('requests.show', $recordRequest))
        ->assertOk()
        ->assertSee('Request rejected.');
});

test('a reason is required to reject a request', function () {
    $recordRequest = RecordRequest::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.reject', $recordRequest), ['reason' => ''])
        ->assertSessionHasErrors('reason');

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::Pending);
});

test('a request already acted on cannot be approved or rejected again', function (string $state) {
    $recordRequest = RecordRequest::factory()->{$state}()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.approve', $recordRequest))->assertForbidden();
    $this->actingAs($staff)->post(route('requests.reject', $recordRequest), ['reason' => 'Because'])->assertForbidden();
})->with(['approved', 'released', 'rejected', 'cancellationRequested', 'cancelled']);

test('students cannot approve or reject requests', function () {
    $recordRequest = RecordRequest::factory()->create();
    $student = User::factory()->create();

    $this->actingAs($student)->post(route('requests.approve', $recordRequest))->assertForbidden();
    $this->actingAs($student)->post(route('requests.reject', $recordRequest), ['reason' => 'Because'])->assertForbidden();
});

test('guests are redirected to log in when approving or rejecting requests', function () {
    $recordRequest = RecordRequest::factory()->create();

    $this->post(route('requests.approve', $recordRequest))->assertRedirect(route('login'));
    $this->post(route('requests.reject', $recordRequest), ['reason' => 'Because'])->assertRedirect(route('login'));
});

test('rejecting from the details page opens a modal that asks for the reason', function () {
    $recordRequest = RecordRequest::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.show', $recordRequest))
        ->assertOk()
        ->assertSee('Reject this request?')
        ->assertSee('Reason for rejection')
        ->assertSee('Reject request');
});

test('rejecting without a reason is refused and the reason must be given', function () {
    $recordRequest = RecordRequest::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.reject', $recordRequest), ['reason' => ''])
        ->assertSessionHasErrors('reason');

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::Pending);
});

test('approving from the details page asks for confirmation first', function () {
    $recordRequest = RecordRequest::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.show', $recordRequest))
        ->assertOk()
        ->assertSee('Approve this request?')
        ->assertSee('Yes, approve');
});
