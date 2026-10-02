<?php

use App\Enums\DocumentType;

test('the landing page lists every document type with its per-copy fee', function () {
    $response = $this->get('/')->assertOk();

    foreach (DocumentType::cases() as $documentType) {
        $response->assertSee($documentType->label())->assertSee($documentType->formattedFee());
    }
});

test('the landing page states the configured cancellation window', function () {
    config(['school.cancellation_window_days' => 5]);

    $this->get('/')->assertOk()->assertSee('Cancel within 5 days');
});

test('the landing page links to the public request pages', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('record-requests.create'), false)
        ->assertSee(route('record-requests.status.create'), false)
        ->assertSee(route('record-requests.cancel.create'), false)
        ->assertSee(route('record-requests.history.create'), false);
});

test('the landing page says an approved request can no longer be cancelled', function () {
    $this->get('/')->assertOk()->assertSee('Once the registrar approves it, it can no longer be cancelled.');
});

test('a one-day cancellation window reads "within a day" on the landing page', function () {
    config(['school.cancellation_window_days' => 1]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Cancel within a day')
        ->assertSee('cancel a pending request within a day of submitting it.')
        ->assertDontSee('1 days');
});
