<?php

use App\Models\RecordRequest;
use App\Models\User;

test('waiting requests are queued first come first served with urgent ones ahead', function () {
    $first = RecordRequest::factory()->create(['created_at' => now()->subHours(3)]);
    $second = RecordRequest::factory()->approved()->create(['created_at' => now()->subHours(2)]);
    $urgent = RecordRequest::factory()->urgent()->create(['created_at' => now()->subHour()]);
    $finished = RecordRequest::factory()->rejected()->create(['created_at' => now()->subHours(4)]);

    expect(RecordRequest::queuePositions())->toBe([
        $urgent->id => 1,
        $first->id => 2,
        $second->id => 3,
    ]);

    expect($first->queuePosition())->toBe(2)
        ->and($finished->queuePosition())->toBeNull()
        ->and(RecordRequest::queueLength())->toBe(3);
});

test('the queue updates when a request leaves it', function () {
    $first = RecordRequest::factory()->create(['created_at' => now()->subHours(2)]);
    $second = RecordRequest::factory()->create(['created_at' => now()->subHour()]);

    expect($second->queuePosition())->toBe(2);

    $first->reject();

    expect($second->queuePosition())->toBe(1);
});

test('the status page shows the requester their place in the queue', function () {
    RecordRequest::factory()->create(['created_at' => now()->subHours(2)]);
    $mine = RecordRequest::factory()->create(['created_at' => now()->subHour()]);

    $this->post(route('record-requests.status.store'), ['reference_no' => $mine->reference_no]);

    $this->get(route('record-requests.status.create'))
        ->assertOk()
        ->assertSee('Place in queue')
        ->assertSee('#2 of 2')
        ->assertSee('1 request ahead of you');
});

test('the status page shows no queue place once the request is released or rejected', function () {
    $rejected = RecordRequest::factory()->rejected()->create();

    $this->post(route('record-requests.status.store'), ['reference_no' => $rejected->reference_no]);

    $this->get(route('record-requests.status.create'))->assertOk()->assertDontSee('Place in queue');
});

test('the staff list shows the queue column', function () {
    RecordRequest::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.index'))->assertOk()->assertSee('Queue');
});
