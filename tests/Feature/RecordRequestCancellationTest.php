<?php

use App\Enums\RequestStatus;
use App\Mail\RecordRequestCancellationSubmitted;
use App\Mail\RecordRequestReceived;
use App\Models\RecordRequest;
use App\Models\User;
use App\Notifications\RecordRequestCancellationRequested;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * @return array<string, mixed>
 */
function validCancellation(array $overrides = []): array
{
    return [
        'reference_no' => 'REQ-PLACEHOLDER',
        'email' => 'requester@example.com',
        'reason' => 'I no longer need this document.',
        ...$overrides,
    ];
}

test('submitting a request emails a confirmation with a working cancel link', function () {
    Mail::fake();

    $this->post(route('record-requests.store'), validRecordRequest(['email' => 'maria.cruz@gmail.com']));

    Mail::assertSent(RecordRequestReceived::class, function (RecordRequestReceived $mail) {
        return $mail->hasTo('maria.cruz@gmail.com')
            && $mail->hasSubject('Your reference number: '.$mail->recordRequest->reference_no)
            && str_contains($mail->render(), $mail->recordRequest->reference_no)
            && str_contains($mail->render(), route('record-requests.cancel.create', ['reference_no' => $mail->recordRequest->reference_no]));
    });
});

test('a request is still saved when the confirmation email fails', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('Mail server down'));

    $this->post(route('record-requests.store'), validRecordRequest())->assertSessionHas('reference_no');

    expect(RecordRequest::count())->toBe(1);
});

test('the cancel form pre-fills the reference number from the query string', function () {
    $this->get(route('record-requests.cancel.create', ['reference_no' => 'REQ-ABCD1234']))
        ->assertOk()
        ->assertSee('value="REQ-ABCD1234"', escape: false);
});

test('a matching pending request can be cancelled with a reason', function () {
    Mail::fake();
    Notification::fake();

    $recordRequest = RecordRequest::factory()->create(['email' => 'maria@example.com']);

    $this->post(route('record-requests.cancel.store'), validCancellation([
        'reference_no' => strtolower($recordRequest->reference_no),
        'email' => 'MARIA@example.com',
        'reason' => 'Submitted by mistake.',
    ]))->assertRedirect(route('record-requests.cancel.create'))->assertSessionHas('status');

    $recordRequest->refresh();
    expect($recordRequest->status)->toBe(RequestStatus::CancellationRequested)
        ->and($recordRequest->cancellation_requested_at)->not->toBeNull()
        ->and($recordRequest->cancellation_reason)->toBe('Submitted by mistake.')
        ->and($recordRequest->cancelled_at)->toBeNull();
});

test('the requester is emailed a confirmation once the cancellation is submitted', function () {
    Mail::fake();
    Notification::fake();

    $recordRequest = RecordRequest::factory()->create(['email' => 'maria@example.com']);

    $this->post(route('record-requests.cancel.store'), validCancellation([
        'reference_no' => $recordRequest->reference_no,
        'email' => 'maria@example.com',
        'reason' => 'Changed my mind.',
    ]));

    Mail::assertSent(RecordRequestCancellationSubmitted::class, function (RecordRequestCancellationSubmitted $mail) use ($recordRequest) {
        return $mail->hasTo('maria@example.com')
            && $mail->recordRequest->is($recordRequest)
            && str_contains($mail->render(), 'Changed my mind.');
    });
});

test('requesting cancellation notifies active staff and admins, but not inactive or student accounts', function () {
    Mail::fake();
    Notification::fake();

    $staff = User::factory()->staff()->create();
    $admin = User::factory()->admin()->create();
    $inactiveStaff = User::factory()->staff()->inactive()->create();
    $student = User::factory()->create();

    $recordRequest = RecordRequest::factory()->create(['email' => 'maria@example.com']);
    $this->post(route('record-requests.cancel.store'), validCancellation([
        'reference_no' => $recordRequest->reference_no,
        'email' => 'maria@example.com',
    ]));

    Notification::assertSentTo([$staff, $admin], RecordRequestCancellationRequested::class);
    Notification::assertNotSentTo([$inactiveStaff, $student], RecordRequestCancellationRequested::class);
});

test('the cancellation-requested notification links to the request', function () {
    Mail::fake();
    Notification::fake();

    $staff = User::factory()->staff()->create();
    $recordRequest = RecordRequest::factory()->create(['email' => 'maria@example.com']);

    $this->post(route('record-requests.cancel.store'), validCancellation([
        'reference_no' => $recordRequest->reference_no,
        'email' => 'maria@example.com',
    ]));

    Notification::assertSentTo($staff, function (RecordRequestCancellationRequested $notification) use ($recordRequest) {
        return $notification->toArray($notification)['url'] === route('requests.show', $recordRequest)
            && $notification->toArray($notification)['reference_no'] === $recordRequest->reference_no;
    });
});

test('a reason is required', function () {
    $recordRequest = RecordRequest::factory()->create(['email' => 'maria@example.com']);

    $this->post(route('record-requests.cancel.store'), validCancellation([
        'reference_no' => $recordRequest->reference_no,
        'email' => 'maria@example.com',
        'reason' => '',
    ]))->assertSessionHasErrors('reason');

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::Pending);
});

test('an unknown reference number and email combination is rejected', function () {
    Mail::fake();

    $this->post(route('record-requests.cancel.store'), validCancellation(['reference_no' => 'REQ-UNKNOWN']))
        ->assertSessionHasErrors('reference_no');

    Mail::assertNothingSent();
});

test('the email must match the one on file', function () {
    Mail::fake();
    $recordRequest = RecordRequest::factory()->create(['email' => 'maria@example.com']);

    $this->post(route('record-requests.cancel.store'), validCancellation([
        'reference_no' => $recordRequest->reference_no,
        'email' => 'someone.else@example.com',
    ]))->assertSessionHasErrors('reference_no');

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::Pending);
    Mail::assertNothingSent();
});

test('a request cannot be cancelled once the cancellation window has passed', function () {
    Mail::fake();
    $recordRequest = RecordRequest::factory()->create(['email' => 'maria@example.com']);

    $this->travel(config('school.cancellation_window_days') + 1)->days();

    $this->post(route('record-requests.cancel.store'), validCancellation([
        'reference_no' => $recordRequest->reference_no,
        'email' => 'maria@example.com',
    ]))->assertSessionHasErrors('reference_no');

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::Pending);
});

test('a request can still be cancelled right up to the edge of the window', function () {
    Mail::fake();
    Notification::fake();
    $recordRequest = RecordRequest::factory()->create(['email' => 'maria@example.com']);

    $this->travel(config('school.cancellation_window_days') * 24 - 1)->hours();

    $this->post(route('record-requests.cancel.store'), validCancellation([
        'reference_no' => $recordRequest->reference_no,
        'email' => 'maria@example.com',
    ]))->assertSessionHas('status');

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::CancellationRequested);
});

test('an already approved request cannot be cancelled online', function () {
    Mail::fake();
    $recordRequest = RecordRequest::factory()->approved()->create(['email' => 'maria@example.com']);

    $this->post(route('record-requests.cancel.store'), validCancellation([
        'reference_no' => $recordRequest->reference_no,
        'email' => 'maria@example.com',
    ]))->assertSessionHasErrors('reference_no');

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::Approved);
});

test('a request that already has a cancellation pending is not requested again', function () {
    Mail::fake();
    $recordRequest = RecordRequest::factory()->cancellationRequested()->create(['email' => 'maria@example.com']);

    $this->post(route('record-requests.cancel.store'), validCancellation([
        'reference_no' => $recordRequest->reference_no,
        'email' => 'maria@example.com',
    ]))->assertSessionHasErrors('reference_no');
});

test('an already cancelled request cannot be cancelled again', function () {
    Mail::fake();
    $recordRequest = RecordRequest::factory()->cancelled()->create(['email' => 'maria@example.com']);

    $this->post(route('record-requests.cancel.store'), validCancellation([
        'reference_no' => $recordRequest->reference_no,
        'email' => 'maria@example.com',
    ]))->assertSessionHasErrors('reference_no');
});

test('requesting cancellation is rate limited', function () {
    Mail::fake();
    Notification::fake();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('record-requests.cancel.store'), validCancellation(['reference_no' => 'REQ-X']))
            ->assertSessionHasErrors('reference_no');
    }

    $this->post(route('record-requests.cancel.store'), validCancellation(['reference_no' => 'REQ-X']))
        ->assertSessionHasErrors('throttle');
});

test('staff see cancelled requests as cancelled', function () {
    $recordRequest = RecordRequest::factory()->cancelled()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.index'))->assertSee('Cancelled');
    $this->actingAs($staff)->get(route('requests.show', $recordRequest))->assertOk()->assertSee('Cancelled');
});

test('staff can see the cancellation reason on the request', function () {
    Mail::fake();
    Notification::fake();

    $recordRequest = RecordRequest::factory()->create(['email' => 'maria@example.com']);
    $this->post(route('record-requests.cancel.store'), validCancellation([
        'reference_no' => $recordRequest->reference_no,
        'email' => 'maria@example.com',
        'reason' => 'No longer needed for employment.',
    ]));

    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.show', $recordRequest))
        ->assertOk()
        ->assertSee('No longer needed for employment.');
});
