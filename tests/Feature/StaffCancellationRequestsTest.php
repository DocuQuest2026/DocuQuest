<?php

use App\Enums\RequestStatus;
use App\Models\RecordRequest;
use App\Models\User;

test('staff and administrators see only cancellation requests on the page', function (string $state) {
    $cancellationRequested = RecordRequest::factory()->cancellationRequested()->create();
    $pending = RecordRequest::factory()->create();
    $office = User::factory()->{$state}()->create();

    $this->actingAs($office)->get(route('requests.cancellations'))
        ->assertOk()
        ->assertSee($cancellationRequested->reference_no)
        ->assertDontSee($pending->reference_no);
})->with(['staff', 'admin']);

test('the cancellation reason is shown on the page', function () {
    $recordRequest = RecordRequest::factory()->cancellationRequested()->create(['cancellation_reason' => 'No longer needed.']);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.cancellations'))
        ->assertOk()
        ->assertSee('No longer needed.');
});

test('staff can confirm a cancellation from the page', function () {
    $recordRequest = RecordRequest::factory()->cancellationRequested()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->post(route('requests.confirm-cancellation', $recordRequest))
        ->assertRedirect();

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::Cancelled);
});

test('staff can deny a cancellation from the page', function () {
    $recordRequest = RecordRequest::factory()->cancellationRequested()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->post(route('requests.deny-cancellation', $recordRequest))
        ->assertRedirect();

    expect($recordRequest->fresh()->status)->toBe(RequestStatus::Pending);
});

test('students can not see the cancellation requests page', function () {
    $student = User::factory()->create();

    $this->actingAs($student)->get(route('requests.cancellations'))->assertForbidden();
});

test('guests are redirected to log in when viewing cancellation requests', function () {
    $this->get(route('requests.cancellations'))->assertRedirect(route('login'));
});

test('the nav shows a badge with the pending cancellation count', function () {
    RecordRequest::factory()->cancellationRequested()->create();
    RecordRequest::factory()->cancellationRequested()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Cancellation requests')
        ->assertSee('2');
});

test('the nav shows no badge when there are no pending cancellations', function () {
    $staff = User::factory()->staff()->create();

    $response = $this->actingAs($staff)->get(route('dashboard'))->assertOk();

    $response->assertSeeInOrder(['Cancellation requests', 'Student requests']);
});

test('the cancelled tab shows only fully cancelled requests, not pending ones', function () {
    $cancelled = RecordRequest::factory()->cancelled()->create();
    $pending = RecordRequest::factory()->cancellationRequested()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.cancellations', ['status' => 'cancelled']))
        ->assertOk()
        ->assertSee($cancelled->reference_no)
        ->assertDontSee($pending->reference_no);
});

test('the pending tab does not show fully cancelled requests', function () {
    $cancelled = RecordRequest::factory()->cancelled()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.cancellations'))
        ->assertOk()
        ->assertDontSee($cancelled->reference_no);
});

test('the cancelled tab has no confirm or deny actions', function () {
    $cancelled = RecordRequest::factory()->cancelled()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.cancellations', ['status' => 'cancelled']))
        ->assertOk()
        ->assertDontSee(__('Confirm'))
        ->assertDontSee(__('Keep request'));
});
