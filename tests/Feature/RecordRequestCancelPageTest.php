<?php

use App\Models\RecordRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * The fields the cancel form posts.
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function cancelPayload(array $overrides = []): array
{
    return [
        'reference_no' => 'REQ-PLACEHOLDER',
        'email' => 'requester@example.com',
        'reason' => 'I no longer need this document.',
        ...$overrides,
    ];
}

test('the cancel form warns about the cancellation window and that an approved request can not be cancelled', function () {
    config(['school.cancellation_window_days' => 1]);

    $this->get(route('record-requests.cancel.create'))
        ->assertOk()
        ->assertSee('You can cancel a pending request within a day of submitting. Once your request is approved, it can no longer be cancelled.')
        ->assertSee('Request cancellation')
        ->assertSee('Reason for cancellation');
});

test('the cancel form follows the cancellation window setting', function () {
    config(['school.cancellation_window_days' => 3]);

    $this->get(route('record-requests.cancel.create'))
        ->assertOk()
        ->assertSee('You can cancel a pending request within 3 days of submitting.');
});

test('the cancel form still posts to the cancel route with a csrf token and no browser confirm box', function () {
    $this->get(route('record-requests.cancel.create'))
        ->assertOk()
        ->assertSeeHtml('action="'.route('record-requests.cancel.store').'"')
        ->assertSeeHtml('name="_token"')
        ->assertDontSeeHtml('onsubmit="return confirm(');
});

test('the cancel form asks for confirmation in a pop-up in the middle of the screen before sending', function () {
    $this->get(route('record-requests.cancel.create'))
        ->assertOk()
        ->assertSeeHtml('role="dialog"')
        ->assertSeeHtml('items-center justify-center')
        ->assertSee('Cancel this request?')
        ->assertSee('Registrar staff will review it before it is cancelled.')
        ->assertSee('Keep my request')
        ->assertSee('Yes, request cancellation')
        ->assertSeeHtml('x-on:submit.prevent="openReview($event.target)"');
});

test('the reason is limited to 1000 characters and the reference number is typed as a code', function () {
    $this->get(route('record-requests.cancel.create'))
        ->assertOk()
        ->assertSeeHtml('maxlength="1000"')
        ->assertSeeHtml('autocapitalize="characters"')
        ->assertSeeHtml('autocomplete="off"')
        ->assertSeeHtml('spellcheck="false"');
});

test('the reference number and reason typed before an error come back on the form', function () {
    $this->followingRedirects()
        ->from(route('record-requests.cancel.create'))
        ->post(route('record-requests.cancel.store'), cancelPayload([
            'reference_no' => 'REQ-NOPE0000',
            'email' => 'maria@example.com',
            'reason' => 'I typed this reason.',
        ]))
        ->assertOk()
        ->assertSee('REQ-NOPE0000')
        ->assertSee('I typed this reason.')
        ->assertSee('No pending request was found for that reference number and email.');
});

test('once a cancellation is sent the page shows a confirmation card instead of the form', function () {
    Mail::fake();
    Notification::fake();
    $recordRequest = RecordRequest::factory()->create(['email' => 'maria@example.com']);

    $this->post(route('record-requests.cancel.store'), cancelPayload([
        'reference_no' => $recordRequest->reference_no,
        'email' => 'maria@example.com',
    ]));

    $this->get(route('record-requests.cancel.create'))
        ->assertOk()
        ->assertSee('Cancellation Requested')
        ->assertSee('Your cancellation request has been sent to the registrar and is awaiting review. We have emailed you a confirmation.')
        ->assertSee('The registrar reviews your request')
        ->assertSee('Check Status')
        ->assertSee(route('record-requests.status.create'), false)
        ->assertSee('Cancel another request')
        ->assertDontSee('Reason for cancellation')
        ->assertDontSee('Check your request status first');
});

test('the confirmation card only appears right after sending, not on later visits', function () {
    $this->get(route('record-requests.cancel.create'))
        ->assertOk()
        ->assertDontSee('Cancellation Requested')
        ->assertSee('Reason for cancellation');
});

test('the cancel form links to the status page for people who only want to check', function () {
    $this->get(route('record-requests.cancel.create'))
        ->assertOk()
        ->assertSee('Not sure?')
        ->assertSee('Check your request status first')
        ->assertSee(route('record-requests.status.create'), false);
});
