<?php

use App\Enums\RequestStatus;
use App\Models\RecordRequest;
use App\Models\User;

test('staff and administrators can list and view student requests', function (string $state) {
    $recordRequest = RecordRequest::factory()->create();
    $office = User::factory()->{$state}()->create();

    $this->actingAs($office)->get(route('requests.index'))
        ->assertOk()
        ->assertSee($recordRequest->reference_no)
        ->assertSee($recordRequest->student_no);

    $this->actingAs($office)->get(route('requests.show', $recordRequest))
        ->assertOk()
        ->assertSee($recordRequest->purpose)
        ->assertSee($recordRequest->maskedEmail())
        ->assertDontSee($recordRequest->email);
})->with(['staff', 'admin']);

test('staff can filter student requests by status', function () {
    $pending = RecordRequest::factory()->create(['status' => RequestStatus::Pending]);
    $approved = RecordRequest::factory()->create(['status' => RequestStatus::Approved]);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.index', ['status' => RequestStatus::Pending->value]))
        ->assertOk()
        ->assertSee($pending->reference_no)
        ->assertDontSee($approved->reference_no);
});

test('student requests are listed first-come, first-served, oldest at the top', function () {
    $staff = User::factory()->staff()->create();

    $first = RecordRequest::factory()->create(['created_at' => now()->subDays(2)]);
    $second = RecordRequest::factory()->create(['created_at' => now()->subDay()]);
    $third = RecordRequest::factory()->create(['created_at' => now()]);

    $this->actingAs($staff)->get(route('requests.index'))
        ->assertOk()
        ->assertSeeInOrder([$first->reference_no, $second->reference_no, $third->reference_no]);
});

test('an unfilterable status falls back to showing all student requests', function () {
    $pending = RecordRequest::factory()->create(['status' => RequestStatus::Pending]);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.index', ['status' => RequestStatus::Cancelled->value]))
        ->assertOk()
        ->assertSee($pending->reference_no);
});

test('students can not see student requests', function () {
    $recordRequest = RecordRequest::factory()->create();
    $student = User::factory()->create();

    $this->actingAs($student)->get(route('requests.index'))->assertForbidden();
    $this->actingAs($student)->get(route('requests.show', $recordRequest))->assertForbidden();
});

test('guests are redirected to log in when viewing student requests', function () {
    $recordRequest = RecordRequest::factory()->create();

    $this->get(route('requests.index'))->assertRedirect(route('login'));
    $this->get(route('requests.show', $recordRequest))->assertRedirect(route('login'));
});

test('a released request from before the claim-available-at field existed still renders', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $recordRequest->release->update(['claim_available_at' => null]);

    $viewer = User::factory()->admin()->create();

    $this->actingAs($viewer)->get(route('requests.show', $recordRequest))
        ->assertOk()
        ->assertDontSee('Available to claim from');
});

test('a request still shows who released it after that staff account is deleted', function () {
    $releasedBy = User::factory()->staff()->create(['name' => 'Former Staff']);
    $recordRequest = RecordRequest::factory()->released()->create();
    $recordRequest->release->update(['released_by' => $releasedBy->id]);
    $releasedBy->delete();

    $viewer = User::factory()->admin()->create();

    $this->actingAs($viewer)->get(route('requests.show', $recordRequest))
        ->assertOk()
        ->assertSee('Former Staff');
});

test('the nav shows a badge with the count of new pending requests', function () {
    RecordRequest::factory()->count(3)->create(['status' => RequestStatus::Pending]);
    RecordRequest::factory()->approved()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Student requests', '3']);
});

test('the nav shows no badge on student requests when nothing is pending', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('dashboard'))->assertOk()->assertSee('Student requests');
});
