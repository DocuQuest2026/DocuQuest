<?php

use App\Enums\RequestStatus;
use App\Mail\RecordRequestReceived;
use App\Models\RecordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('submitting a request emails a confirmation with a working cancel link', function () {
    Mail::fake();

    $this->post(route('record-requests.store'), validRecordRequest(['email' => 'maria.cruz@gmail.com']));

    Mail::assertSent(RecordRequestReceived::class, function (RecordRequestReceived $mail) {
        return $mail->hasTo('maria.cruz@gmail.com')
            && $mail->hasSubject('Your reference number: '.$mail->recordRequest->reference_no)
            && str_contains($mail->render(), $mail->recordRequest->reference_no)
            && str_contains($mail->render(), '/request/cancel/'.$mail->recordRequest->reference_no);
    });
});

test('a request is still saved when the confirmation email fails', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('Mail server down'));

    $this->post(route('record-requests.store'), validRecordRequest())->assertSessionHas('reference_no');

    expect(RecordRequest::count())->toBe(1);
});

test('the signed link asks for confirmation without cancelling', function () {
    $recordRequest = RecordRequest::factory()->create();

    $this->get($recordRequest->cancellationUrl())
        ->assertOk()
        ->assertSee('Cancel this request?');

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::Pending);
});

test('confirming through the signed link requests cancellation without cancelling outright', function () {
    $recordRequest = RecordRequest::factory()->create();
    $url = $recordRequest->cancellationUrl();

    $this->post($url)->assertRedirect($url);

    $recordRequest->refresh();
    expect($recordRequest->status)->toBe(RequestStatus::CancellationRequested)
        ->and($recordRequest->cancellation_requested_at)->not->toBeNull()
        ->and($recordRequest->cancelled_at)->toBeNull();

    $this->get($url)->assertOk()->assertSee('Cancellation requested');
});

test('cancel links that are unsigned, tampered with, or expired are rejected', function () {
    $recordRequest = RecordRequest::factory()->create();

    $this->post(route('record-requests.cancel.store', $recordRequest))->assertForbidden();
    $this->post($recordRequest->cancellationUrl().'&tampered=1')->assertForbidden();

    $expired = $recordRequest->cancellationUrl();
    $this->travel(15)->days();
    $this->post($expired)->assertForbidden();

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::Pending);
});

test('a request cannot be cancelled once the cancellation window has passed', function () {
    $recordRequest = RecordRequest::factory()->create();
    $url = $recordRequest->cancellationUrl();

    $this->travel(config('school.cancellation_window_days') + 1)->days();

    $this->get($url)->assertOk()->assertSee('Cancellation window has passed');
    $this->post($url)->assertRedirect($url);

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::Pending);
});

test('a request can still be cancelled right up to the edge of the window', function () {
    $recordRequest = RecordRequest::factory()->create();
    $url = $recordRequest->cancellationUrl();

    $this->travel(config('school.cancellation_window_days') * 24 - 1)->hours();

    $this->post($url)->assertRedirect($url);

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::CancellationRequested);
});

test('a link can be requested by reference number and email', function () {
    Mail::fake();
    $recordRequest = RecordRequest::factory()->create(['email' => 'maria@example.com']);

    $this->post(route('record-requests.cancel.send'), [
        'reference_no' => strtolower($recordRequest->reference_no),
        'email' => 'MARIA@example.com',
    ])->assertSessionHas('status');

    Mail::assertSent(RecordRequestReceived::class, fn ($mail) => $mail->hasTo('maria@example.com'));
});

test('no link is sent for mismatched, unknown, or already cancelled requests, and the answer never differs', function () {
    Mail::fake();
    $pending = RecordRequest::factory()->create(['email' => 'maria@example.com']);
    $cancelled = RecordRequest::factory()->cancelled()->create(['email' => 'maria@example.com']);

    $responses = collect([
        ['reference_no' => $pending->reference_no, 'email' => 'someone.else@example.com'],
        ['reference_no' => 'REQ-UNKNOWN', 'email' => 'maria@example.com'],
        ['reference_no' => $cancelled->reference_no, 'email' => 'maria@example.com'],
    ])->map(fn (array $data) => $this->post(route('record-requests.cancel.send'), $data)->baseResponse->getSession()->get('status'));

    Mail::assertNothingSent();
    expect($responses->unique())->toHaveCount(1)->and($responses->first())->not->toBeNull();
});

test('requesting cancellation links is rate limited', function () {
    Mail::fake();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('record-requests.cancel.send'), ['reference_no' => 'REQ-X', 'email' => 'a@example.com'])
            ->assertSessionHas('status');
    }

    $this->post(route('record-requests.cancel.send'), ['reference_no' => 'REQ-X', 'email' => 'a@example.com'])
        ->assertSessionHasErrors('throttle');
});

test('staff see cancelled requests as cancelled', function () {
    $recordRequest = RecordRequest::factory()->cancelled()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.index'))->assertSee('Cancelled');
    $this->actingAs($staff)->get(route('requests.show', $recordRequest))->assertOk()->assertSee('Cancelled');
});
