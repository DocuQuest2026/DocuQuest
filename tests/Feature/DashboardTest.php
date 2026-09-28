<?php

use App\Models\RecordRequest;
use App\Models\User;

test('staff see requests needing action on the dashboard', function () {
    $pending = RecordRequest::factory()->create();
    $approved = RecordRequest::factory()->approved()->create();
    $cancellationRequested = RecordRequest::factory()->cancellationRequested()->create();
    $released = RecordRequest::factory()->released()->create();
    $rejected = RecordRequest::factory()->rejected()->create();
    $cancelled = RecordRequest::factory()->cancelled()->create();

    $staff = User::factory()->staff()->create();

    $response = $this->actingAs($staff)->get(route('dashboard'))->assertOk();

    $response->assertSee($pending->reference_no)
        ->assertSee($approved->reference_no)
        ->assertSee($cancellationRequested->reference_no)
        ->assertDontSee($released->reference_no)
        ->assertDontSee($rejected->reference_no)
        ->assertDontSee($cancelled->reference_no);
});

test('the dashboard says nothing needs attention when there are no actionable requests', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Nothing needs your attention right now.');
});
