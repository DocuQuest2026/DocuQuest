<?php

use App\Models\RecordRequest;

test('the landing page links to the status check page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Check Status')
        ->assertSee(route('record-requests.status.create'));
});

test('the status form can be rendered without signing in', function () {
    $this->get(route('record-requests.status.create'))->assertOk()->assertSee('Reference number');
});

test('a valid reference number shows the request status', function () {
    $recordRequest = RecordRequest::factory()->approved()->create();

    $this->post(route('record-requests.status.store'), ['reference_no' => $recordRequest->reference_no])
        ->assertRedirect(route('record-requests.status.create'));

    $this->get(route('record-requests.status.create'))
        ->assertOk()
        ->assertSee($recordRequest->reference_no)
        ->assertSee('Approved — being prepared');
});

test('the reference number is not case sensitive', function () {
    $recordRequest = RecordRequest::factory()->create();

    $this->post(route('record-requests.status.store'), ['reference_no' => strtolower($recordRequest->reference_no)])
        ->assertRedirect(route('record-requests.status.create'));

    $this->get(route('record-requests.status.create'))->assertOk()->assertSee($recordRequest->reference_no);
});

test('a released request shows when it is available to claim', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $claimAvailableAt = $recordRequest->release->claim_available_at;

    $this->post(route('record-requests.status.store'), ['reference_no' => $recordRequest->reference_no]);

    $this->get(route('record-requests.status.create'))
        ->assertOk()
        ->assertSee('Ready to claim')
        ->assertSee($claimAvailableAt->format('M j, Y g:i A'));
});

test('an unknown reference number shows an error and no data', function () {
    $this->post(route('record-requests.status.store'), ['reference_no' => 'REQ-UNKNOWN'])
        ->assertSessionHasErrors('reference_no');

    $this->get(route('record-requests.status.create'))
        ->assertOk()
        ->assertDontSee('Ready to claim')
        ->assertDontSee('Being processed');
});

test('an archived request can not be looked up', function () {
    $recordRequest = RecordRequest::factory()->create();
    $recordRequest->delete();

    $this->post(route('record-requests.status.store'), ['reference_no' => $recordRequest->reference_no])
        ->assertSessionHasErrors('reference_no');
});

test('the reference number is required', function () {
    $this->post(route('record-requests.status.store'), [])->assertSessionHasErrors('reference_no');
});

test('checking status is rate limited', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('record-requests.status.store'), ['reference_no' => 'REQ-X'])
            ->assertSessionHasErrors('reference_no');
    }

    $this->post(route('record-requests.status.store'), ['reference_no' => 'REQ-X'])
        ->assertSessionHasErrors('throttle');
});

test('checking status does not expose the requester\'s personal details', function () {
    $recordRequest = RecordRequest::factory()->create(['email' => 'maria.secret@gmail.com', 'purpose' => 'Confidential purpose text']);

    $this->post(route('record-requests.status.store'), ['reference_no' => $recordRequest->reference_no]);

    $this->get(route('record-requests.status.create'))
        ->assertOk()
        ->assertDontSee('maria.secret@gmail.com')
        ->assertDontSee('Confidential purpose text');
});
