<?php

use App\Enums\DocumentType;
use App\Enums\RequestStatus;
use App\Mail\RecordRequestHistoryCode;
use App\Models\RecordRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

/**
 * Walk through the email and code steps for the email, as a requester would, and return the
 * unlocked history page.
 */
function openHistoryFor(string $email): TestResponse
{
    Mail::fake();

    test()->post(route('record-requests.history.store'), ['email' => $email]);

    $code = null;

    Mail::assertSent(RecordRequestHistoryCode::class, function (RecordRequestHistoryCode $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    test()->post(route('record-requests.history.verify'), ['code' => $code]);

    return test()->get(route('record-requests.history.create'))->assertOk();
}

test('the first step shows the email field and which step the requester is on', function () {
    $this->get(route('record-requests.history.create'))
        ->assertOk()
        ->assertSee('Email me a code')
        ->assertSee('Use the same email you entered on the request form.')
        ->assertSeeInOrder(['Email', 'Code'])
        ->assertDontSee('6-digit code');
});

test('the second step asks for the code, offers a new one, and numbers only', function () {
    $this->withSession(['history_pending_email' => 'maria.cruz@gmail.com'])
        ->get(route('record-requests.history.create'))
        ->assertOk()
        ->assertSee('Enter the 6-digit code we emailed to maria.cruz@gmail.com.')
        ->assertSee('6-digit code')
        ->assertSee('it expires in 10 minutes')
        ->assertSee('Did not get a code?')
        ->assertSee('Send a new code')
        ->assertSee('Use a different email')
        ->assertSeeHtml('name="email" value="maria.cruz@gmail.com"')
        ->assertSeeHtml('inputmode="numeric"')
        ->assertSeeHtml('pattern="[0-9]{6}"')
        ->assertSeeHtml('minlength="6"')
        ->assertSeeHtml("replace(/\\D/g, '')");
});

test('a new code can be requested from the code step', function () {
    Mail::fake();
    $recordRequest = RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com']);

    $this->post(route('record-requests.history.store'), ['email' => $recordRequest->email]);
    $this->post(route('record-requests.history.store'), ['email' => $recordRequest->email])
        ->assertRedirect(route('record-requests.history.create'));

    Mail::assertSent(RecordRequestHistoryCode::class, 2);
});

test('the unlocked history lists each request with its copies, amount to pay and date', function () {
    $transcript = RecordRequest::factory()->create([
        'email' => 'maria.cruz@gmail.com',
        'document_type' => DocumentType::TranscriptOfRecords,
        'copies' => 2,
        'status' => RequestStatus::Pending,
    ]);
    $enrolment = RecordRequest::factory()->approved()->create([
        'email' => 'maria.cruz@gmail.com',
        'document_type' => DocumentType::CertificateOfEnrolment,
        'copies' => 3,
    ]);

    openHistoryFor('maria.cruz@gmail.com')
        ->assertSee('2 requests')
        ->assertSee($transcript->reference_no)
        ->assertSee($enrolment->reference_no)
        ->assertSee('Transcript of Records')
        ->assertSeeHtml('&times; 2 copies')
        ->assertSee('₱300.00')
        ->assertSeeHtml('₱150.00 &times; 2 copies')
        ->assertSee('₱150.00')
        ->assertSeeHtml('₱50.00 &times; 3 copies')
        ->assertSee('Amount to pay')
        ->assertSee($transcript->created_at->format('M j, Y'));
});

test('the unlocked history says how long the view stays open', function () {
    RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com']);

    openHistoryFor('maria.cruz@gmail.com')
        ->assertSee('For your privacy, this view closes by itself after 15 minutes.');
});

test('a request that can still be cancelled offers a cancel link in the history, others do not', function () {
    config(['school.cancellation_window_days' => 1]);
    $cancellable = RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com', 'status' => RequestStatus::Pending]);
    $approved = RecordRequest::factory()->approved()->create(['email' => 'maria.cruz@gmail.com']);

    openHistoryFor('maria.cruz@gmail.com')
        ->assertSee('You can still cancel it within a day of submitting.')
        ->assertSee(route('record-requests.cancel.create', ['reference_no' => $cancellable->reference_no]), false)
        ->assertDontSee(route('record-requests.cancel.create', ['reference_no' => $approved->reference_no]), false);
});

test('rejected and cancelled requests show no amount to pay in the history', function () {
    RecordRequest::factory()->rejected()->create(['email' => 'maria.cruz@gmail.com', 'document_type' => DocumentType::TranscriptOfRecords, 'copies' => 2]);
    RecordRequest::factory()->cancelled()->create(['email' => 'maria.cruz@gmail.com', 'document_type' => DocumentType::TranscriptOfRecords, 'copies' => 2]);

    openHistoryFor('maria.cruz@gmail.com')
        ->assertSee('Rejected')
        ->assertSee('Cancelled')
        ->assertDontSee('Amount to pay')
        ->assertDontSee('₱300.00');
});

test('a claimed document shows as claimed with the date and a total fee in the history', function () {
    $recordRequest = RecordRequest::factory()->released()->create(['email' => 'maria.cruz@gmail.com', 'document_type' => DocumentType::TranscriptOfRecords, 'copies' => 1]);
    $claimedAt = now()->subHours(2);
    $recordRequest->release->update(['claimed_at' => $claimedAt]);

    openHistoryFor('maria.cruz@gmail.com')
        ->assertSee('Claimed')
        ->assertSee($claimedAt->format('M j, Y g:i A'))
        ->assertSee('Total fee')
        ->assertDontSee('Amount to pay')
        ->assertDontSee('Ready to claim');
});

test('a released document that is waiting to be picked up shows when it is available', function () {
    $recordRequest = RecordRequest::factory()->released()->create(['email' => 'maria.cruz@gmail.com']);

    openHistoryFor('maria.cruz@gmail.com')
        ->assertSee('Ready to claim')
        ->assertSee('Available to claim from')
        ->assertSee($recordRequest->release->claim_available_at->format('M j, Y g:i A'));
});

test('an email with no unarchived requests gets no code, so there is no history to show', function () {
    Mail::fake();

    $this->post(route('record-requests.history.store'), ['email' => 'nobody@gmail.com']);

    Mail::assertNothingSent();
});

test('the badge a requester sees matches each status', function (RequestStatus $status, string $label) {
    $recordRequest = RecordRequest::factory()->make(['status' => $status]);

    expect($recordRequest->requesterStatus()['label'])->toBe($label)
        ->and($recordRequest->requesterStatus()['classes'])->toBeString()->not->toBeEmpty();
})->with([
    'pending' => [RequestStatus::Pending, 'Being processed'],
    'approved' => [RequestStatus::Approved, 'Approved — being prepared'],
    'released' => [RequestStatus::Released, 'Ready to claim'],
    'rejected' => [RequestStatus::Rejected, 'Rejected'],
    'cancellation requested' => [RequestStatus::CancellationRequested, 'Cancellation requested'],
    'cancelled' => [RequestStatus::Cancelled, 'Cancelled'],
]);

test('the badge reads claimed once a released document was picked up', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $recordRequest->release->update(['claimed_at' => now()]);

    expect($recordRequest->fresh()->requesterStatus()['label'])->toBe('Claimed');
});
