<?php

use App\Mail\RecordRequestHistoryCode;
use App\Models\RecordRequest;
use Illuminate\Support\Facades\Mail;

/**
 * Ask for a code for the email and return the code that was emailed, or null if none was sent.
 */
function requestHistoryCode(string $email): ?string
{
    $code = null;

    test()->post(route('record-requests.history.store'), ['email' => $email]);

    Mail::assertSent(RecordRequestHistoryCode::class, function (RecordRequestHistoryCode $mail) use (&$code, $email) {
        if ($mail->hasTo($email)) {
            $code = $mail->code;
        }

        return true;
    });

    return $code;
}

test('the history form can be rendered without signing in', function () {
    $this->get(route('record-requests.history.create'))
        ->assertOk()
        ->assertSee(__('Email me a code'));
});

test('a code is emailed when the email has requests', function () {
    Mail::fake();
    RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com']);

    $this->post(route('record-requests.history.store'), ['email' => 'Maria.Cruz@gmail.com '])
        ->assertRedirect(route('record-requests.history.create'));

    Mail::assertSent(RecordRequestHistoryCode::class, function (RecordRequestHistoryCode $mail) {
        return $mail->hasTo('maria.cruz@gmail.com')
            && preg_match('/^\d{6}$/', $mail->code) === 1
            && str_contains($mail->render(), $mail->code);
    });
});

test('no code is sent for an unknown email, and the page does not reveal that', function () {
    Mail::fake();
    RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com']);

    $this->post(route('record-requests.history.store'), ['email' => 'nobody@gmail.com']);

    Mail::assertNothingSent();

    $this->get(route('record-requests.history.create'))
        ->assertOk()
        ->assertSee(__('6-digit code'));
});

test('no code is sent when all of the email\'s requests are archived', function () {
    Mail::fake();
    RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com'])->delete();

    $this->post(route('record-requests.history.store'), ['email' => 'maria.cruz@gmail.com']);

    Mail::assertNothingSent();
});

test('the email must be a valid email address', function () {
    $this->post(route('record-requests.history.store'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});

test('the correct code unlocks only that email\'s history', function () {
    Mail::fake();
    $mine = RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com']);
    $archived = RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com']);
    $archived->delete();
    $someoneElses = RecordRequest::factory()->create(['email' => 'juan.reyes@gmail.com']);

    $code = requestHistoryCode('maria.cruz@gmail.com');

    $this->post(route('record-requests.history.verify'), ['code' => $code])
        ->assertRedirect(route('record-requests.history.create'));

    $this->get(route('record-requests.history.create'))
        ->assertOk()
        ->assertSee($mine->reference_no)
        ->assertDontSee($archived->reference_no)
        ->assertDontSee($someoneElses->reference_no);
});

test('the history stays locked without a verified code', function () {
    $recordRequest = RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com']);

    $this->get(route('record-requests.history.create'))
        ->assertOk()
        ->assertDontSee($recordRequest->reference_no);
});

test('a wrong code does not unlock the history', function () {
    Mail::fake();
    $recordRequest = RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com']);

    $code = requestHistoryCode('maria.cruz@gmail.com');
    $wrongCode = $code === '000000' ? '111111' : '000000';

    $this->post(route('record-requests.history.verify'), ['code' => $wrongCode])
        ->assertSessionHasErrors('code');

    $this->get(route('record-requests.history.create'))->assertDontSee($recordRequest->reference_no);
});

test('a code stops working after too many wrong attempts', function () {
    Mail::fake();
    RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com']);

    $code = requestHistoryCode('maria.cruz@gmail.com');
    $wrongCode = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $attempt) {
        $this->post(route('record-requests.history.verify'), ['code' => $wrongCode]);
    }

    $this->post(route('record-requests.history.verify'), ['code' => $code])
        ->assertSessionHasErrors('code');
});

test('a code can not be used twice', function () {
    Mail::fake();
    RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com']);

    $code = requestHistoryCode('maria.cruz@gmail.com');

    $this->post(route('record-requests.history.verify'), ['code' => $code]);
    $this->post(route('record-requests.history.clear'));

    $this->withSession(['history_pending_email' => 'maria.cruz@gmail.com'])
        ->post(route('record-requests.history.verify'), ['code' => $code])
        ->assertSessionHasErrors('code');
});
test('the unlocked history times out', function () {
    Mail::fake();
    $recordRequest = RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com']);

    $this->post(route('record-requests.history.verify'), ['code' => requestHistoryCode('maria.cruz@gmail.com')]);

    $this->travel(16)->minutes();

    $this->get(route('record-requests.history.create'))->assertDontSee($recordRequest->reference_no);
});

test('the code must be six digits', function () {
    $this->post(route('record-requests.history.verify'), ['code' => 'abc'])->assertSessionHasErrors('code');
});

test('too many code requests for the same email are throttled', function () {
    Mail::fake();
    RecordRequest::factory()->create(['email' => 'maria.cruz@gmail.com']);

    foreach (range(1, 3) as $attempt) {
        $this->post(route('record-requests.history.store'), ['email' => 'maria.cruz@gmail.com']);
    }

    $this->post(route('record-requests.history.store'), ['email' => 'maria.cruz@gmail.com'])
        ->assertSessionHasErrors('throttle');

    Mail::assertSent(RecordRequestHistoryCode::class, 3);
});
