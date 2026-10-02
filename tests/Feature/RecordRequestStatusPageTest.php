<?php

use App\Enums\DocumentType;
use App\Enums\RequestStatus;
use App\Models\RecordRequest;
use Illuminate\Testing\TestResponse;

/**
 * Look a request up the way the page does: post the reference number, then follow the redirect.
 */
function lookUpStatus(RecordRequest $recordRequest): TestResponse
{
    test()->post(route('record-requests.status.store'), ['reference_no' => $recordRequest->reference_no]);

    return test()->get(route('record-requests.status.create'))->assertOk();
}

test('a pending request shows it is being processed, the processing time and a progress timeline', function () {
    config(['school.processing_days' => 3]);
    $recordRequest = RecordRequest::factory()->create(['status' => RequestStatus::Pending]);

    lookUpStatus($recordRequest)
        ->assertSee('Being processed')
        ->assertSee('The registrar is processing your request. This takes up to 3 days, depending on the document requested.')
        ->assertSeeInOrder(['Request submitted', 'The registrar is processing for approval', 'Ready to claim'])
        ->assertSee($recordRequest->document_type->label())
        ->assertSee($recordRequest->created_at->format('M j, Y'));
});

test('a pending request still inside the cancellation window offers a link to cancel it', function () {
    config(['school.cancellation_window_days' => 1]);
    $recordRequest = RecordRequest::factory()->create(['status' => RequestStatus::Pending]);

    lookUpStatus($recordRequest)
        ->assertSee('You can still cancel it within a day of submitting.')
        ->assertSee(route('record-requests.cancel.create', ['reference_no' => $recordRequest->reference_no]), false);
});

test('a pending request past the cancellation window no longer offers a cancel link', function () {
    config(['school.cancellation_window_days' => 1]);
    $recordRequest = RecordRequest::factory()->create(['status' => RequestStatus::Pending, 'created_at' => now()->subDays(2)]);

    lookUpStatus($recordRequest)
        ->assertSee('Being processed')
        ->assertDontSee('You can still cancel it')
        ->assertDontSee(route('record-requests.cancel.create', ['reference_no' => $recordRequest->reference_no]), false);
});

test('an approved request says it can no longer be cancelled', function () {
    $recordRequest = RecordRequest::factory()->approved()->create();

    lookUpStatus($recordRequest)
        ->assertSee('Approved — being prepared')
        ->assertSee('Your request is approved and your document is being prepared. It can no longer be cancelled.')
        ->assertDontSee('You can still cancel it')
        ->assertDontSee(route('record-requests.cancel.create', ['reference_no' => $recordRequest->reference_no]), false);
});

test('a released request shows it is ready to claim and what to bring', function () {
    $recordRequest = RecordRequest::factory()->released()->create();

    lookUpStatus($recordRequest)
        ->assertSee('Ready to claim')
        ->assertSee('Your document is ready. Bring a valid ID, or have your named representative bring theirs.')
        ->assertDontSee('You claimed this document');
});

test('a claimed document shows as claimed with the date it was picked up', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $claimedAt = now()->subHours(3);
    $recordRequest->release->update(['claimed_at' => $claimedAt]);

    lookUpStatus($recordRequest)
        ->assertSee('Claimed')
        ->assertSee('You claimed this document on '.$claimedAt->format('M j, Y g:i A').'.')
        ->assertDontSee('Your document is ready. Bring a valid ID');
});

test('a rejected, cancellation-requested or cancelled request shows a message and no timeline', function (string $state, string $badge, string $message) {
    $recordRequest = RecordRequest::factory()->{$state}()->create();

    lookUpStatus($recordRequest)
        ->assertSee($badge)
        ->assertSee($message)
        ->assertDontSee('Approved by the registrar')
        ->assertDontSee('Your document is ready to be picked up.');
})->with([
    'rejected' => ['rejected', 'Rejected', 'The registrar could not approve this request. Please contact the registrar for details.'],
    'cancellation requested' => ['cancellationRequested', 'Cancellation requested', 'You asked to cancel this request. The registrar will confirm it.'],
    'cancelled' => ['cancelled', 'Cancelled', 'This request was cancelled.'],
]);

test('the lookup form is offered again after a result so another request can be checked', function () {
    lookUpStatus(RecordRequest::factory()->create())->assertSee('Check another reference number');

    $this->get(route('record-requests.status.create'))->assertSee('Reference number')->assertDontSee('Check another reference number');
});

test('the reference number field is for typing a code, not for autofill suggestions', function () {
    $this->get(route('record-requests.status.create'))
        ->assertOk()
        ->assertSeeHtml('autocomplete="off"')
        ->assertSeeHtml('autocapitalize="characters"')
        ->assertSeeHtml('spellcheck="false"');
});

test('the status page links to the history page for people who lost their reference number', function () {
    $this->get(route('record-requests.status.create'))
        ->assertOk()
        ->assertSee('Lost your reference number?')
        ->assertSee(route('record-requests.history.create'), false);
});

test('the status page shows how many copies of the document were requested', function (int $copies, string $expected) {
    $recordRequest = RecordRequest::factory()->create(['copies' => $copies]);

    lookUpStatus($recordRequest)
        ->assertSee($recordRequest->document_type->label())
        ->assertSeeHtml('&times; '.$expected);
})->with([
    'one copy' => [1, '1 copy'],
    'two copies' => [2, '2 copies'],
    'ten copies' => [10, '10 copies'],
]);

test('a request computes its total fee from the per-copy fee and the copies', function () {
    $recordRequest = RecordRequest::factory()->make(['document_type' => DocumentType::TranscriptOfRecords, 'copies' => 3]);

    expect($recordRequest->totalFee())->toBe(450)
        ->and($recordRequest->formattedTotalFee())->toBe('₱450.00');
});

test('the status page shows the amount to pay with how it adds up', function (DocumentType $document, int $copies, string $amount, string $breakdown) {
    $recordRequest = RecordRequest::factory()->create(['document_type' => $document, 'copies' => $copies, 'status' => RequestStatus::Pending]);

    lookUpStatus($recordRequest)
        ->assertSee('Amount to pay')
        ->assertSee($amount)
        ->assertSeeHtml($breakdown);
})->with([
    'transcript, 2 copies' => [DocumentType::TranscriptOfRecords, 2, '₱300.00', '₱150.00 &times; 2 copies'],
    'enrolment, 1 copy' => [DocumentType::CertificateOfEnrolment, 1, '₱50.00', '₱50.00 &times; 1 copy'],
    'graduation, 10 copies' => [DocumentType::CertificateOfGraduation, 10, '₱1,000.00', '₱100.00 &times; 10 copies'],
]);

test('the amount to pay is shown for approved and released requests too', function () {
    lookUpStatus(RecordRequest::factory()->approved()->create(['document_type' => DocumentType::CertifiedTrueCopyOfGrades, 'copies' => 2]))
        ->assertSee('Amount to pay')
        ->assertSee('₱200.00');

    lookUpStatus(RecordRequest::factory()->released()->create(['document_type' => DocumentType::CertificateOfGoodMoral, 'copies' => 4]))
        ->assertSee('Amount to pay')
        ->assertSee('₱200.00');
});

test('a claimed document labels the amount as the total fee', function () {
    $recordRequest = RecordRequest::factory()->released()->create(['document_type' => DocumentType::TranscriptOfRecords, 'copies' => 1]);
    $recordRequest->release->update(['claimed_at' => now()]);

    lookUpStatus($recordRequest)
        ->assertSee('Total fee')
        ->assertSee('₱150.00')
        ->assertDontSee('Amount to pay');
});

test('rejected and cancelled requests show no amount to pay', function (string $state) {
    $recordRequest = RecordRequest::factory()->{$state}()->create(['document_type' => DocumentType::TranscriptOfRecords, 'copies' => 2]);

    lookUpStatus($recordRequest)
        ->assertDontSee('Amount to pay')
        ->assertDontSee('₱300.00');
})->with(['rejected', 'cancelled']);

test('while a request is being processed the approval step says the registrar is processing it', function () {
    config(['school.processing_days' => 4]);
    $recordRequest = RecordRequest::factory()->create(['status' => RequestStatus::Pending]);

    lookUpStatus($recordRequest)
        ->assertSee('The registrar is processing for approval')
        ->assertSee('This takes up to 4 days, depending on the document.')
        ->assertDontSee('Your request is being processed by the registrar.')
        ->assertDontSee('Approved by the registrar')
        ->assertDontSee('Your request is reviewed and approved.');
});

test('once a request is approved or released the approval step says it was reviewed and approved', function (string $state) {
    config(['school.processing_days' => 4]);
    $recordRequest = RecordRequest::factory()->{$state}()->create();

    lookUpStatus($recordRequest)
        ->assertSee('Approved by the registrar')
        ->assertSee('Your request is reviewed and approved.')
        ->assertDontSee('The registrar is processing for approval')
        ->assertDontSee('This takes up to 4 days, depending on the document.');
})->with(['approved', 'released']);
