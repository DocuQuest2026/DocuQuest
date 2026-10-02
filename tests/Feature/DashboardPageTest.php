<?php

use App\Enums\RequestStatus;
use App\Models\RecordRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('the dashboard greets the staff member by first name with their role and today\'s date', function () {
    $staff = User::factory()->staff()->create(['name' => 'Ana Reyes']);

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Welcome back, Ana')
        ->assertSee('Registrar Staff')
        ->assertSee(now()->format('l, F j, Y'));
});

test('the dashboard tiles count what needs action and link to the right lists', function () {
    RecordRequest::factory()->count(2)->create(['status' => RequestStatus::Pending]);
    RecordRequest::factory()->approved()->create();
    RecordRequest::factory()->cancellationRequested()->count(3)->create();
    RecordRequest::factory()->released()->count(2)->create();
    RecordRequest::factory()->released()->create()->release->update(['claimed_at' => now()]);
    RecordRequest::factory()->rejected()->count(4)->create();

    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['2', 'Pending', 'Waiting for approval', '1', 'Approved', 'Ready to release', '3', 'Cancellations', 'Students asking to cancel', '2', 'Released', 'Ready for pickup'])
        ->assertSee(route('requests.index', ['status' => 'pending']), false)
        ->assertSee(route('requests.index', ['status' => 'approved']), false)
        ->assertSee(route('requests.index', ['status' => 'released']), false)
        ->assertSee(route('requests.cancellations'), false);
});

test('the requests needing action list holds pending, approved and cancellation requests but not released ones', function () {
    $pending = RecordRequest::factory()->create(['status' => RequestStatus::Pending]);
    $approved = RecordRequest::factory()->approved()->create();
    $cancellation = RecordRequest::factory()->cancellationRequested()->create();
    $released = RecordRequest::factory()->released()->create();

    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee($pending->reference_no)
        ->assertSee($approved->reference_no)
        ->assertSee($cancellation->reference_no)
        ->assertDontSee($released->reference_no)
        ->assertDontSee('Showing the oldest');
});

test('the dashboard says how many of the requests needing action are listed when there are more than ten', function () {
    RecordRequest::factory()->count(12)->create(['status' => RequestStatus::Pending]);

    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Showing the oldest 10 of 12 requests needing action.');
});

test('each request needing action shows its document, copies and what to do next', function () {
    $pending = RecordRequest::factory()->create(['status' => RequestStatus::Pending, 'copies' => 3]);
    $approved = RecordRequest::factory()->approved()->create();
    $cancellation = RecordRequest::factory()->cancellationRequested()->create();

    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee($pending->document_type->label())
        ->assertSeeHtml('&times; 3')
        ->assertSee('Needs approval')
        ->assertSee('Ready to release')
        ->assertSee('Cancellation requested')
        ->assertSee(route('requests.show', $pending), false)
        ->assertSee(route('requests.show', $approved), false)
        ->assertSee(route('requests.show', $cancellation), false);
});

test('the dashboard reads the counts from the cache instead of running an extra count query', function () {
    RecordRequest::factory()->count(3)->create(['status' => RequestStatus::Pending]);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('dashboard'))->assertOk();

    DB::enableQueryLog();
    $this->actingAs($staff)->get(route('dashboard'))->assertOk();

    $recordRequestQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'record_requests'))
        ->count();

    expect($recordRequestQueries)->toBe(1);
});

test('someone who can not manage requests only sees the welcome banner', function () {
    $student = User::factory()->create(['name' => 'Student Person']);

    $this->actingAs($student)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Welcome back, Student')
        ->assertDontSee('Requests needing action')
        ->assertDontSee('Students asking to cancel');
});

test('the navigation shows the brand, the user menu with name, email and role, and marks the current page', function () {
    $staff = User::factory()->staff()->create(['name' => 'Ana Reyes', 'email' => 'ana.reyes@docuquest.test']);

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Registrar workspace')
        ->assertSee('Ana Reyes')
        ->assertSee('ana.reyes@docuquest.test')
        ->assertSee('Registrar Staff')
        ->assertSeeHtml('aria-current="page"')
        ->assertSee('Log Out');
});

test('only administrators see the staff accounts link in the navigation', function () {
    $this->actingAs(User::factory()->staff()->create())->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Staff accounts');

    $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Staff accounts');
});

test('the navigation marks the student requests page as current there', function () {
    $this->actingAs(User::factory()->staff()->create())->get(route('requests.index'))
        ->assertOk()
        ->assertSeeHtml('aria-current="page"');
});
