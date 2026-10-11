<?php

use App\Mail\RecordRequestApproved;
use App\Mail\RecordRequestReceived;
use App\Models\RecordRequest;
use Illuminate\Support\Carbon;

test('each document has its own processing time', function () {
    expect(documentType('transcript_of_records')->processingDays())->toBe(5)
        ->and(documentType('certificate_of_enrolment')->processingDays())->toBe(1);
});

test('the estimated ready date counts working days and skips weekends', function () {
    $friday = Carbon::parse('2026-10-09 10:00');

    expect(documentType('transcript_of_records')->estimatedReadyDate($friday)->toDateString())->toBe('2026-10-16');
});

test('only requests still waiting on staff have an estimated ready date', function () {
    $pending = RecordRequest::factory()->create(['document_type_id' => documentType('transcript_of_records')->id, 'created_at' => '2026-10-09 10:00:00']);
    $rejected = RecordRequest::factory()->rejected()->create();

    expect($pending->estimatedReadyDate()->toDateString())->toBe('2026-10-16')
        ->and($rejected->estimatedReadyDate())->toBeNull();
});

test('the status page shows the estimated ready date for a waiting request only', function () {
    $this->travelTo('2026-10-09 10:00:00');

    $pending = RecordRequest::factory()->create(['document_type_id' => documentType('transcript_of_records')->id]);

    $this->post(route('record-requests.status.store'), ['reference_no' => $pending->reference_no]);
    $this->get(route('record-requests.status.create'))
        ->assertOk()
        ->assertSee('Estimated ready by')
        ->assertSee('Fri, Oct 16, 2026')
        ->assertSee('Up to 5 working days');

    $rejected = RecordRequest::factory()->rejected()->create();

    $this->post(route('record-requests.status.store'), ['reference_no' => $rejected->reference_no]);
    $this->get(route('record-requests.status.create'))->assertOk()->assertDontSee('Estimated ready by');
});

test('the request form shows how long each document takes', function () {
    $response = $this->get(route('record-requests.create'))->assertOk()->assertSee('Estimated ready by');

    expect($response->getContent())->toMatch('/days\\\\+u0022:5/');
});

test('the confirmation and approval emails include the estimated ready date', function () {
    $recordRequest = RecordRequest::factory()->create(['document_type_id' => documentType('transcript_of_records')->id, 'created_at' => '2026-10-09 10:00:00']);

    (new RecordRequestReceived($recordRequest))->assertSeeInHtml('Fri, Oct 16, 2026');
    (new RecordRequestApproved($recordRequest))->assertSeeInHtml('Fri, Oct 16, 2026');
});
