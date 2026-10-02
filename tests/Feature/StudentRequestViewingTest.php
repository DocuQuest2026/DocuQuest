<?php

use App\Enums\RequestStatus;
use App\Models\RecordRequest;
use App\Models\User;
use Livewire\Livewire;

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

    $first = RecordRequest::factory()->create(['created_at' => now()->startOfMonth()]);
    $second = RecordRequest::factory()->create(['created_at' => now()->startOfMonth()->addHour()]);
    $third = RecordRequest::factory()->create(['created_at' => now()->startOfMonth()->addHours(2)]);

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

test('staff can search student requests by name, reference number or student number', function () {
    $juan = RecordRequest::factory()->create(['first_name' => 'Juan', 'middle_name' => 'Dela', 'last_name' => 'Cruz']);
    $maria = RecordRequest::factory()->create(['first_name' => 'Maria', 'middle_name' => null, 'last_name' => 'Santos']);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.index', ['search' => 'juan cruz']))
        ->assertOk()
        ->assertSee($juan->reference_no)
        ->assertDontSee($maria->reference_no);

    $this->actingAs($staff)->get(route('requests.index', ['search' => strtolower($maria->reference_no)]))
        ->assertOk()
        ->assertSee($maria->reference_no)
        ->assertDontSee($juan->reference_no);

    $this->actingAs($staff)->get(route('requests.index', ['search' => $juan->student_no]))
        ->assertOk()
        ->assertSee($juan->reference_no)
        ->assertDontSee($maria->reference_no);
});

test('searching combines with the status filter and shows an empty message when nothing matches', function () {
    $pending = RecordRequest::factory()->create(['first_name' => 'Juan', 'status' => RequestStatus::Pending]);
    RecordRequest::factory()->create(['first_name' => 'Juan', 'status' => RequestStatus::Approved]);
    $staff = User::factory()->staff()->create();

    $listed = Livewire::actingAs($staff)
        ->test('request-list')
        ->call('setStatus', RequestStatus::Pending->value)
        ->set('search', 'Juan')
        ->instance()
        ->recordRequests;

    expect($listed->pluck('id')->all())->toBe([$pending->id]);

    $this->actingAs($staff)->get(route('requests.index', ['search' => 'zzzznomatch']))
        ->assertOk()
        ->assertSee('No requests match your search.');
});

test('the request list updates as staff type, without a page reload', function () {
    $juan = RecordRequest::factory()->create(['first_name' => 'Juan', 'middle_name' => null, 'last_name' => 'Cruz']);
    $maria = RecordRequest::factory()->create(['first_name' => 'Maria', 'middle_name' => null, 'last_name' => 'Santos']);

    Livewire::actingAs(User::factory()->staff()->create())
        ->test('request-list')
        ->assertSee($juan->reference_no)
        ->assertSee($maria->reference_no)
        ->set('search', 'juan')
        ->assertSee($juan->reference_no)
        ->assertDontSee($maria->reference_no)
        ->set('search', strtolower($maria->reference_no))
        ->assertSee($maria->reference_no)
        ->assertDontSee($juan->reference_no);
});

test('searching by name or reference code lists the matching requests with their name and code', function () {
    $juan = RecordRequest::factory()->create(['first_name' => 'Juan', 'middle_name' => null, 'last_name' => 'Cruz']);
    RecordRequest::factory()->create(['first_name' => 'Juana', 'middle_name' => null, 'last_name' => 'Reyes']);
    $maria = RecordRequest::factory()->create(['first_name' => 'Maria', 'middle_name' => null, 'last_name' => 'Santos']);

    Livewire::actingAs(User::factory()->staff()->create())
        ->test('request-list')
        ->set('search', 'juan')
        ->assertSeeInOrder(['Juan Cruz', $juan->reference_no])
        ->assertSee('Juana Reyes')
        ->assertDontSee('Maria Santos')
        ->set('search', $maria->reference_no)
        ->assertSee('Maria Santos');
});

test('students can not use the live request list', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('request-list')
        ->assertForbidden();
});

test('search results put names starting with the typed letters first', function () {
    $contains = RecordRequest::factory()->create(['first_name' => 'Alan', 'middle_name' => null, 'last_name' => 'Ramos', 'created_at' => now()->startOfMonth()]);
    $startsWith = RecordRequest::factory()->create(['first_name' => 'Ramon', 'middle_name' => null, 'last_name' => 'Abad', 'created_at' => now()->startOfMonth()->addHour()]);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('requests.index', ['search' => 'ra']))
        ->assertOk()
        ->assertSeeInOrder([$startsWith->reference_no, $contains->reference_no]);
});

test('the request list highlights the active tab and skips release records on tabs without them', function () {
    $pending = RecordRequest::factory()->create(['status' => RequestStatus::Pending]);
    $component = Livewire::actingAs(User::factory()->staff()->create())->test('request-list');

    expect($component->instance()->activeTab())->toBe('');

    $component->call('setStatus', 'claimed');
    expect($component->instance()->activeTab())->toBe('claimed');

    $component->call('setStatus', 'pending');
    expect($component->instance()->activeTab())->toBe('pending')
        ->and($component->instance()->recordRequests->first()->relationLoaded('release'))->toBeFalse()
        ->and($component->instance()->recordRequests->first()->is($pending))->toBeTrue();

    $component->call('setStatus', 'cancelled');
    expect($component->instance()->activeTab())->toBe('');
});

test('staff can filter the request list to the requests submitted in one month', function () {
    $lastDayOfJanuary = RecordRequest::factory()->create(['created_at' => '2026-01-31 23:59:59']);
    $firstDayOfJanuary = RecordRequest::factory()->create(['created_at' => '2026-01-01 00:00:00']);
    $december = RecordRequest::factory()->create(['created_at' => '2025-12-31 23:59:59']);
    $february = RecordRequest::factory()->create(['created_at' => '2026-02-01 00:00:00']);

    $component = Livewire::actingAs(User::factory()->staff()->create())
        ->test('request-list')
        ->set('month', '2026-01');

    expect($component->instance()->recordRequests->pluck('id')->all())
        ->toEqualCanonicalizing([$lastDayOfJanuary->id, $firstDayOfJanuary->id]);

    $component->set('month', '2026-02');
    expect($component->instance()->recordRequests->pluck('id')->all())->toBe([$february->id]);

    $component->set('month', '2026-03')->assertSee('No requests were submitted in that month.');

    $component->set('month', '2025-12')->assertSet('month', now()->format('Y-m'));
    expect($component->instance()->recordRequests->pluck('id')->all())->not->toContain($december->id);
});

test('the month filter can not go earlier than the first month the system was used', function () {
    config(['school.first_request_month' => '2026-01']);

    $component = Livewire::withQueryParams(['month' => '2025-06'])
        ->actingAs(User::factory()->staff()->create())
        ->test('request-list')
        ->assertSet('month', now()->format('Y-m'));

    $component->set('month', '2026-01')->assertSet('month', '2026-01');
    $component->set('month', '2025-12')->assertSet('month', now()->format('Y-m'));
    $component->assertSeeHtml('min="2026-01"');
});

test('the request list opens on the current month and falls back to it when the month is cleared', function () {
    $thisMonth = RecordRequest::factory()->create(['created_at' => now()->startOfMonth()->addHour()]);
    $lastMonth = RecordRequest::factory()->create(['created_at' => now()->startOfMonth()->subHour()]);

    $component = Livewire::actingAs(User::factory()->staff()->create())
        ->test('request-list')
        ->assertSet('month', now()->format('Y-m'))
        ->assertSee($thisMonth->reference_no)
        ->assertDontSee($lastMonth->reference_no)
        ->assertDontSee('Any month');

    $component->set('month', $lastMonth->created_at->format('Y-m'))
        ->assertSee($lastMonth->reference_no)
        ->assertDontSee($thisMonth->reference_no);

    $component->set('month', '')->assertSet('month', now()->format('Y-m'));
    $component->set('month', 'garbage')->assertSet('month', now()->format('Y-m'));
});

test('the month filter combines with search and the status tab, and ignores malformed values', function () {
    $matching = RecordRequest::factory()->create(['first_name' => 'Juan', 'status' => RequestStatus::Pending, 'created_at' => '2026-01-10 09:00:00']);
    RecordRequest::factory()->create(['first_name' => 'Juan', 'status' => RequestStatus::Pending, 'created_at' => '2026-02-10 09:00:00']);
    RecordRequest::factory()->create(['first_name' => 'Maria', 'status' => RequestStatus::Pending, 'created_at' => '2026-01-11 09:00:00']);
    RecordRequest::factory()->create(['first_name' => 'Juan', 'status' => RequestStatus::Approved, 'created_at' => '2026-01-12 09:00:00']);

    $component = Livewire::actingAs(User::factory()->staff()->create())
        ->test('request-list')
        ->call('setStatus', RequestStatus::Pending->value)
        ->set('search', 'Juan')
        ->set('month', '2026-01');

    expect($component->instance()->recordRequests->pluck('id')->all())->toBe([$matching->id]);

    $component->set('month', 'not-a-month')->assertSet('month', now()->format('Y-m'));
});

test('the month filter can be opened from a link', function () {
    $january = RecordRequest::factory()->create(['created_at' => '2026-01-15 09:00:00']);
    $february = RecordRequest::factory()->create(['created_at' => '2026-02-15 09:00:00']);

    Livewire::withQueryParams(['month' => '2026-01'])
        ->actingAs(User::factory()->staff()->create())
        ->test('request-list')
        ->assertSet('month', '2026-01')
        ->assertSee($january->reference_no)
        ->assertDontSee($february->reference_no);
});

test('staff can narrow the month to one exact day', function () {
    $morning = RecordRequest::factory()->create(['created_at' => '2026-01-15 00:00:00']);
    $night = RecordRequest::factory()->create(['created_at' => '2026-01-15 23:59:59']);
    $previousDay = RecordRequest::factory()->create(['created_at' => '2026-01-14 23:59:59']);
    $nextDay = RecordRequest::factory()->create(['created_at' => '2026-01-16 00:00:00']);

    $component = Livewire::actingAs(User::factory()->staff()->create())
        ->test('request-list')
        ->set('month', '2026-01')
        ->set('day', '15');

    expect($component->instance()->recordRequests->pluck('id')->all())
        ->toEqualCanonicalizing([$morning->id, $night->id]);

    $component->set('day', '16');
    expect($component->instance()->recordRequests->pluck('id')->all())->toBe([$nextDay->id]);

    $component->set('day', '20')->assertSee('No requests were submitted on that day.');

    $component->set('day', '');
    expect($component->instance()->recordRequests->pluck('id')->all())
        ->toEqualCanonicalizing([$morning->id, $night->id, $previousDay->id, $nextDay->id]);
});

test('the day picker offers every day of the selected month and drops a day the month lacks', function () {
    $component = Livewire::actingAs(User::factory()->staff()->create())
        ->test('request-list')
        ->set('month', '2026-01')
        ->set('day', '31')
        ->assertSet('day', '31')
        ->assertSeeHtml('value="31"');

    expect($component->instance()->daysOfMonth)->toHaveCount(31);

    $component->set('month', '2026-02')->assertSet('day', '');
    expect($component->instance()->daysOfMonth)->toHaveCount(28);

    $component->set('day', '29')->assertSet('day', '');
    $component->set('day', 'garbage')->assertSet('day', '');
});

test('the day filter can be opened from a link', function () {
    $onDay = RecordRequest::factory()->create(['created_at' => '2026-01-15 09:00:00']);
    $otherDay = RecordRequest::factory()->create(['created_at' => '2026-01-20 09:00:00']);

    Livewire::withQueryParams(['month' => '2026-01', 'day' => '15'])
        ->actingAs(User::factory()->staff()->create())
        ->test('request-list')
        ->assertSet('day', '15')
        ->assertSee($onDay->reference_no)
        ->assertDontSee($otherDay->reference_no);
});

test('the tab badges count only the requests from the selected month', function () {
    $inMonth = now()->startOfMonth()->addHour();
    $lastMonth = now()->startOfMonth()->subHour();

    RecordRequest::factory()->count(2)->create(['status' => RequestStatus::Pending, 'created_at' => $inMonth]);
    RecordRequest::factory()->count(5)->create(['status' => RequestStatus::Pending, 'created_at' => $lastMonth]);
    RecordRequest::factory()->approved()->count(3)->create(['created_at' => $inMonth]);
    RecordRequest::factory()->released()->count(2)->create(['created_at' => $inMonth]);
    RecordRequest::factory()->released()->create(['created_at' => $inMonth])->release->update(['claimed_at' => now()]);
    RecordRequest::factory()->rejected()->count(4)->create(['created_at' => $inMonth]);
    RecordRequest::factory()->create(['status' => RequestStatus::Pending, 'created_at' => $inMonth])->delete();

    $component = Livewire::actingAs(User::factory()->staff()->create())->test('request-list');

    $component->assertSet('badges.'.RequestStatus::Pending->value, 2)
        ->assertSet('badges.'.RequestStatus::Approved->value, 3)
        ->assertSet('badges.'.RequestStatus::Released->value, 2)
        ->assertSet('badges.'.RequestStatus::Rejected->value, null);

    $component->set('month', $lastMonth->format('Y-m'))
        ->assertSet('badges.'.RequestStatus::Pending->value, 5)
        ->assertSet('badges.'.RequestStatus::Approved->value, null)
        ->assertSet('badges.'.RequestStatus::Released->value, null);
});

test('a month with no requests shows no badges at all', function () {
    RecordRequest::factory()->count(3)->create(['status' => RequestStatus::Pending, 'created_at' => now()->startOfMonth()->addHour()]);

    $component = Livewire::actingAs(User::factory()->staff()->create())
        ->test('request-list')
        ->set('month', '2026-01');

    $component->assertSet('badges', []);
});

test('the tab badges follow the exact day when one is picked', function () {
    RecordRequest::factory()->count(2)->create(['status' => RequestStatus::Pending, 'created_at' => '2026-01-15 10:00:00']);
    RecordRequest::factory()->count(4)->create(['status' => RequestStatus::Pending, 'created_at' => '2026-01-20 10:00:00']);

    $component = Livewire::actingAs(User::factory()->staff()->create())
        ->test('request-list')
        ->set('month', '2026-01')
        ->assertSet('badges.'.RequestStatus::Pending->value, 6)
        ->set('day', '15')
        ->assertSet('badges.'.RequestStatus::Pending->value, 2)
        ->set('day', '20')
        ->assertSet('badges.'.RequestStatus::Pending->value, 4)
        ->set('day', '')
        ->assertSet('badges.'.RequestStatus::Pending->value, 6);
});

test('only the pending, approved and released tabs have a badge, capped at 9+', function () {
    $html = Livewire::actingAs(User::factory()->staff()->create())->test('request-list')->html();

    expect($html)->toContain("badges['pending']")
        ->toContain("badges['approved']")
        ->toContain("badges['released']")
        ->toContain("'9+'")
        ->not->toContain("badges['rejected']")
        ->not->toContain("badges['claimed']")
        ->not->toContain("badges['archived']");
});

test('the tab badges can not be changed from the browser', function () {
    $component = Livewire::actingAs(User::factory()->staff()->create())->test('request-list');

    expect(fn () => $component->set('badges', ['pending' => 99]))
        ->toThrow(Exception::class, 'Cannot update locked property');
});

test('claiming a document refreshes the released badge right away', function () {
    $recordRequest = RecordRequest::factory()->released()->create();

    expect(RecordRequest::badgeCounts()[RequestStatus::Released->value])->toBe(1);

    $recordRequest->release->update(['claimed_at' => now()]);

    expect(RecordRequest::badgeCounts()[RequestStatus::Released->value] ?? 0)->toBe(0);
});

test('the release records are only loaded for released rows', function () {
    $pending = RecordRequest::factory()->create(['status' => RequestStatus::Pending]);
    $released = RecordRequest::factory()->released()->create();

    $rows = Livewire::actingAs(User::factory()->staff()->create())
        ->test('request-list')
        ->instance()
        ->recordRequests
        ->keyBy('id');

    expect($rows[$released->id]->relationLoaded('release'))->toBeTrue()
        ->and($rows[$pending->id]->relationLoaded('release'))->toBeFalse();
});

test('a list rebuilt from the cache looks the same, including release details and the next-page state', function () {
    // Like the real file cache, store serialized data and refuse to bring back objects.
    config(['cache.stores.array.serialize' => true]);
    Cache::purge('array');

    $released = RecordRequest::factory()->released()->create(['created_at' => now()->startOfMonth()->addHour()]);
    $pending = RecordRequest::factory()->create(['status' => RequestStatus::Pending, 'created_at' => now()->startOfMonth()->addHours(2)]);
    RecordRequest::factory()->count(15)->create(['created_at' => now()->startOfMonth()->addHours(3)]);
    $staff = User::factory()->staff()->create();

    $fresh = Livewire::actingAs($staff)->test('request-list');
    $freshHtml = $fresh->html();

    $cached = Livewire::actingAs($staff)->test('request-list');

    $cached->assertSee($released->reference_no)
        ->assertSee($pending->reference_no)
        ->assertSee('confirm-claim-'.$released->id);

    expect($cached->instance()->recordRequests->hasMorePages())->toBeTrue()
        ->and($cached->instance()->recordRequests->first()->release->claimed_at)->toBeNull()
        ->and($cached->instance()->recordRequests->first()->created_at->equalTo($released->created_at))->toBeTrue()
        ->and($cached->instance()->recordRequests->first()->status)->toBe(RequestStatus::Released)
        ->and(preg_replace('/(wire:snapshot|wire:effects|csrf)[^>]*>/', '', $cached->html()))
        ->toBe(preg_replace('/(wire:snapshot|wire:effects|csrf)[^>]*>/', '', $freshHtml));

    $cached->call('nextPage')->assertSee(RecordRequest::query()->oldest()->orderBy('id')->skip(15)->first()->reference_no);
});

test('a list already viewed is served from the cache until a request or release changes', function () {
    config(['cache.stores.array.serialize' => true]);
    Cache::purge('array');

    $recordRequest = RecordRequest::factory()->released()->create();
    $staff = User::factory()->staff()->create();
    $listQueries = function () {
        return collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'record_requests') || str_contains($query['query'], 'document_releases'))->count();
    };

    Livewire::actingAs($staff)->test('request-list')->assertSee($recordRequest->reference_no);

    DB::enableQueryLog();
    Livewire::actingAs($staff)->test('request-list')->assertSee($recordRequest->reference_no);
    expect($listQueries())->toBe(0);

    $recordRequest->update(['copies' => 7]);
    DB::flushQueryLog();
    Livewire::actingAs($staff)->test('request-list')->assertSee($recordRequest->reference_no);
    expect($listQueries())->toBeGreaterThan(0);

    Livewire::actingAs($staff)->test('request-list');
    DB::flushQueryLog();
    $recordRequest->release->update(['claimed_at' => now()]);
    DB::flushQueryLog();
    Livewire::actingAs($staff)->test('request-list')->assertDontSee('confirm-claim-'.$recordRequest->id);
    expect($listQueries())->toBeGreaterThan(0);
});

test('typed searches are never cached', function () {
    $recordRequest = RecordRequest::factory()->create(['first_name' => 'Zacharias']);
    $staff = User::factory()->staff()->create();

    $component = Livewire::actingAs($staff)->test('request-list')->set('search', 'zacharias');
    $component->assertSee($recordRequest->reference_no);

    DB::enableQueryLog();
    $component->set('search', 'zacharia');
    expect(collect(DB::getQueryLog())->contains(fn (array $query): bool => str_contains($query['query'], 'record_requests')))->toBeTrue();
});
