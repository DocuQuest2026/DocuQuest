<?php

use App\Models\AuditLog;
use App\Models\RecordRequest;
use App\Models\User;

test('staff can mark a released document as claimed, defaulting to now', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $staff = User::factory()->staff()->create();
    $now = now()->format('Y-m-d\TH:i:s');

    $this->actingAs($staff)
        ->post(route('requests.claim', $recordRequest), ['claimed_at' => $now])
        ->assertRedirect();

    $recordRequest->refresh();
    expect($recordRequest->release->claimed_at)->not->toBeNull()
        ->and($recordRequest->release->isClaimed())->toBeTrue();

    expect(AuditLog::where('action', 'request.claimed')->where('subject_id', $recordRequest->id)->exists())->toBeTrue();
});

test('staff can set a different date and time than now', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $staff = User::factory()->staff()->create();
    $claimedAt = now()->subDays(2)->setTime(10, 30);

    $this->actingAs($staff)->post(route('requests.claim', $recordRequest), [
        'claimed_at' => $claimedAt->format('Y-m-d\TH:i'),
    ]);

    expect($recordRequest->release->fresh()->claimed_at->format('Y-m-d H:i'))->toBe($claimedAt->format('Y-m-d H:i'));
});

test('a future claimed_at is rejected', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->post(route('requests.claim', $recordRequest), ['claimed_at' => now()->addDay()->format('Y-m-d\TH:i')])
        ->assertSessionHasErrors('claimed_at');

    expect($recordRequest->release->fresh()->claimed_at)->toBeNull();
});

test('claimed_at is required', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->post(route('requests.claim', $recordRequest), [])
        ->assertSessionHasErrors('claimed_at');
});

test('an already claimed document can not be claimed again', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $recordRequest->release->update(['claimed_at' => now()]);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->post(route('requests.claim', $recordRequest), ['claimed_at' => now()->format('Y-m-d\TH:i')])
        ->assertForbidden();
});

test('a request that has not been released can not be claimed', function (string $state) {
    $recordRequest = $state === 'pending' ? RecordRequest::factory()->create() : RecordRequest::factory()->{$state}()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->post(route('requests.claim', $recordRequest), ['claimed_at' => now()->format('Y-m-d\TH:i')])
        ->assertForbidden();
})->with(['pending', 'approved', 'rejected']);

test('students can not claim a document', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $student = User::factory()->create();

    $this->actingAs($student)
        ->post(route('requests.claim', $recordRequest), ['claimed_at' => now()->format('Y-m-d\TH:i')])
        ->assertForbidden();
});

test('guests are redirected to log in when claiming a document', function () {
    $recordRequest = RecordRequest::factory()->released()->create();

    $this->post(route('requests.claim', $recordRequest), ['claimed_at' => now()->format('Y-m-d\TH:i')])
        ->assertRedirect(route('login'));
});

test('the claim button appears on the index page for released, unclaimed requests', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.index'))
        ->assertOk()
        ->assertSee('confirm-claim-'.$recordRequest->id);
});

test('the claim button does not appear once a document is claimed', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $recordRequest->release->update(['claimed_at' => now()]);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.index'))
        ->assertOk()
        ->assertDontSee('confirm-claim-'.$recordRequest->id);
});

test('the claimed tab shows claimed documents with their claim date', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $claimedAt = now()->subHours(3);
    $recordRequest->release->update(['claimed_at' => $claimedAt]);
    $unclaimed = RecordRequest::factory()->released()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.index', ['status' => 'claimed']))
        ->assertOk()
        ->assertSee($recordRequest->reference_no)
        ->assertSee($claimedAt->format('M j, Y g:i A'))
        ->assertDontSee($unclaimed->reference_no);
});

test('claiming a document moves it out of the Released tab and into Claimed', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.index', ['status' => 'released']))
        ->assertOk()
        ->assertSee($recordRequest->reference_no);

    $this->actingAs($staff)->post(route('requests.claim', $recordRequest), [
        'claimed_at' => now()->format('Y-m-d\TH:i'),
    ]);

    $this->actingAs($staff)->get(route('requests.index', ['status' => 'released']))
        ->assertOk()
        ->assertDontSee($recordRequest->reference_no);

    $this->actingAs($staff)->get(route('requests.index', ['status' => 'claimed']))
        ->assertOk()
        ->assertSee($recordRequest->reference_no);
});

test('the All tab still shows released requests regardless of claim status', function () {
    $claimed = RecordRequest::factory()->released()->create();
    $claimed->release->update(['claimed_at' => now()]);
    $unclaimed = RecordRequest::factory()->released()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.index'))
        ->assertOk()
        ->assertSee($claimed->reference_no)
        ->assertSee($unclaimed->reference_no);
});
