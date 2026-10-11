<?php

use App\Models\AuditLog;
use App\Models\RecordRequest;
use App\Models\User;

test('staff can mark a pending request as urgent and clear the flag', function () {
    $recordRequest = RecordRequest::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.urgent', $recordRequest))->assertRedirect();

    expect($recordRequest->fresh()->is_urgent)->toBeTrue();
    expect(AuditLog::where('action', 'request.marked_urgent')->where('subject_id', $recordRequest->id)->exists())->toBeTrue();

    $this->actingAs($staff)->post(route('requests.urgent', $recordRequest))->assertRedirect();

    expect($recordRequest->fresh()->is_urgent)->toBeFalse();
    expect(AuditLog::where('action', 'request.unmarked_urgent')->where('subject_id', $recordRequest->id)->exists())->toBeTrue();
});

test('a request that is no longer waiting on staff cannot be flagged urgent', function () {
    $recordRequest = RecordRequest::factory()->rejected()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.urgent', $recordRequest))->assertForbidden();

    expect($recordRequest->fresh()->is_urgent)->toBeFalse();
});

test('guests cannot flag a request as urgent', function () {
    $recordRequest = RecordRequest::factory()->create();

    $this->post(route('requests.urgent', $recordRequest))->assertRedirect(route('login'));

    expect($recordRequest->fresh()->is_urgent)->toBeFalse();
});

test('urgent requests are listed ahead of older ones, and rejected urgent ones are not', function () {
    $older = RecordRequest::factory()->create(['created_at' => now()->subHour()]);
    $urgent = RecordRequest::factory()->urgent()->create(['created_at' => now()]);
    $rejectedUrgent = RecordRequest::factory()->rejected()->urgent()->create(['created_at' => now()->subDay()->startOfDay()->addHour()]);

    $ordered = RecordRequest::query()->urgentFirst()->oldest()->orderBy('id')->pluck('id')->all();

    expect($ordered)->toBe([$urgent->id, $rejectedUrgent->id, $older->id]);
});

test('the request details page shows the urgent badge and toggle to staff', function () {
    $recordRequest = RecordRequest::factory()->urgent()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.show', $recordRequest))
        ->assertOk()
        ->assertSee('Urgent')
        ->assertSee('Remove urgent flag');
});

test('the staff list has a flag button only for requests still waiting on staff', function () {
    $waiting = RecordRequest::factory()->create();
    $rejected = RecordRequest::factory()->rejected()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.index'))
        ->assertOk()
        ->assertSee(route('requests.urgent', $waiting), false)
        ->assertDontSee(route('requests.urgent', $rejected), false);
});
