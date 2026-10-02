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
        ->assertSee('Cancellation Requests')
        ->assertSee('2');
});

test('the nav shows no badge when there are no pending cancellations', function () {
    $staff = User::factory()->staff()->create();

    $response = $this->actingAs($staff)->get(route('dashboard'))->assertOk();

    $response->assertSeeInOrder(['Cancellation Requests', 'Student Requests']);
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

test('staff can search cancellation requests by name, reference number or student number', function () {
    $ana = RecordRequest::factory()->cancellationRequested()->create(['first_name' => 'Ana', 'last_name' => 'Reyes', 'student_no' => '2024-0001']);
    $ben = RecordRequest::factory()->cancellationRequested()->create(['first_name' => 'Ben', 'last_name' => 'Cruz', 'student_no' => '2024-0002']);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.cancellations', ['search' => 'ana reyes']))
        ->assertOk()
        ->assertSee($ana->reference_no)
        ->assertDontSee($ben->reference_no);

    $this->actingAs($staff)->get(route('requests.cancellations', ['search' => $ben->reference_no]))
        ->assertOk()
        ->assertSee($ben->reference_no)
        ->assertDontSee($ana->reference_no);

    $this->actingAs($staff)->get(route('requests.cancellations', ['search' => '2024-0002']))
        ->assertOk()
        ->assertSee($ben->reference_no)
        ->assertDontSee($ana->reference_no);

    $this->actingAs($staff)->get(route('requests.cancellations', ['search' => 'nobody']))
        ->assertOk()
        ->assertSee('No cancellation requests match your search or date.');
});

test('staff can filter cancellation requests by the month and day they were requested', function () {
    $early = RecordRequest::factory()->cancellationRequested()->create(['cancellation_requested_at' => '2026-03-05 10:00:00']);
    $late = RecordRequest::factory()->cancellationRequested()->create(['cancellation_requested_at' => '2026-03-20 10:00:00']);
    $otherMonth = RecordRequest::factory()->cancellationRequested()->create(['cancellation_requested_at' => '2026-04-02 10:00:00']);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.cancellations', ['month' => '2026-03']))
        ->assertOk()
        ->assertSee($early->reference_no)
        ->assertSee($late->reference_no)
        ->assertDontSee($otherMonth->reference_no);

    $this->actingAs($staff)->get(route('requests.cancellations', ['month' => '2026-03', 'day' => '20']))
        ->assertOk()
        ->assertSee($late->reference_no)
        ->assertDontSee($early->reference_no)
        ->assertDontSee($otherMonth->reference_no);
});

test('the date filter on the cancelled tab uses the day the request was cancelled', function () {
    $cancelledInMarch = RecordRequest::factory()->cancelled()->create(['cancelled_at' => '2026-03-10 09:00:00']);
    $cancelledInApril = RecordRequest::factory()->cancelled()->create(['cancelled_at' => '2026-04-10 09:00:00']);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.cancellations', ['status' => 'cancelled', 'month' => '2026-03']))
        ->assertOk()
        ->assertSee($cancelledInMarch->reference_no)
        ->assertDontSee($cancelledInApril->reference_no);
});

test('an invalid month or day on the cancellation page is ignored', function () {
    $recordRequest = RecordRequest::factory()->cancellationRequested()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.cancellations', ['month' => 'nonsense', 'day' => '99']))
        ->assertOk()
        ->assertSee($recordRequest->reference_no);
});

test('the cancellation page opens on today and can be widened to other days', function () {
    $today = RecordRequest::factory()->cancellationRequested()->create(['cancellation_requested_at' => now()]);
    $earlier = RecordRequest::factory()->cancellationRequested()->create(['cancellation_requested_at' => now()->subMonths(2)]);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.cancellations'))
        ->assertOk()
        ->assertSee($today->reference_no)
        ->assertDontSee($earlier->reference_no)
        ->assertSee('value="'.now()->format('Y-m').'"', false);

    $this->actingAs($staff)->get(route('requests.cancellations', ['month' => '', 'day' => '']))
        ->assertOk()
        ->assertSee($today->reference_no)
        ->assertSee($earlier->reference_no);
});
