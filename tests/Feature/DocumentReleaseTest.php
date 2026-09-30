<?php

use App\Enums\RequestStatus;
use App\Enums\ValidIdType;
use App\Mail\RecordRequestReleased;
use App\Models\AuditLog;
use App\Models\RecordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * @return array<string, mixed>
 */
function validRelease(array $overrides = []): array
{
    return [
        'representative_name' => 'Juan Dela Cruz',
        'claim_available_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ...$overrides,
    ];
}

test('the release form shows and pre-fills the designated representative', function () {
    $recordRequest = RecordRequest::factory()->approved()->create(['designated_representative_name' => 'Pedro Reyes']);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.release.create', $recordRequest))
        ->assertOk()
        ->assertSee('Pedro Reyes')
        ->assertSee('value="Pedro Reyes"', escape: false);
});

test('the release form reminds staff which ID to check for the representative', function () {
    $recordRequest = RecordRequest::factory()->approved()->create([
        'designated_representative_name' => 'Pedro Reyes',
        'designated_representative_id_type' => 'government_id',
    ]);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.release.create', $recordRequest))
        ->assertOk()
        ->assertSee(ValidIdType::GovernmentId->label());
});

test('staff can release an approved request', function () {
    Storage::fake('local');
    Mail::fake();

    $recordRequest = RecordRequest::factory()->approved()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->post(route('requests.release.store', $recordRequest), validRelease())
        ->assertRedirect(route('requests.show', $recordRequest));

    $recordRequest->refresh();
    expect($recordRequest->status)->toBe(RequestStatus::Released);

    $release = $recordRequest->release()->first();
    expect($release)->not->toBeNull()
        ->and($release->representative_name)->toBe('Juan Dela Cruz')
        ->and($release->released_by)->toBe($staff->id)
        ->and($release->verification_token)->not->toBeNull()
        ->and($release->pdf_path)->not->toBeNull()
        ->and($release->claim_available_at)->not->toBeNull();

    Storage::disk('local')->assertExists($release->pdf_path);
});

test('releasing emails the requester that their document is ready to be claimed, naming the representative', function () {
    Storage::fake('local');
    Mail::fake();

    $recordRequest = RecordRequest::factory()->approved()->create(['email' => 'maria@example.com']);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.release.store', $recordRequest), validRelease(['representative_name' => 'Juan Dela Cruz']));

    Mail::assertSent(RecordRequestReleased::class, fn ($mail) => $mail->hasTo('maria@example.com')
        && $mail->hasSubject('Ready to claim: '.$recordRequest->reference_no)
        && str_contains($mail->render(), 'Juan Dela Cruz'));
});

test('releasing emails the requester the date and time the document is available to claim', function () {
    Storage::fake('local');
    Mail::fake();

    $recordRequest = RecordRequest::factory()->approved()->create(['email' => 'maria@example.com']);
    $staff = User::factory()->staff()->create();
    $claimAvailableAt = now()->addDays(2)->setTime(14, 0);

    $this->actingAs($staff)->post(route('requests.release.store', $recordRequest), validRelease([
        'claim_available_at' => $claimAvailableAt->format('Y-m-d\TH:i'),
    ]));

    Mail::assertSent(RecordRequestReleased::class, fn ($mail) => str_contains($mail->render(), $claimAvailableAt->format('M j, Y g:i A')));
});

test('the claim available date and time is required', function () {
    Storage::fake('local');

    $recordRequest = RecordRequest::factory()->approved()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->post(route('requests.release.store', $recordRequest), validRelease(['claim_available_at' => '']))
        ->assertSessionHasErrors('claim_available_at');
});

test('releasing logs a released audit entry', function () {
    Storage::fake('local');
    Mail::fake();

    $recordRequest = RecordRequest::factory()->approved()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.release.store', $recordRequest), validRelease());

    $releasedLog = AuditLog::where('action', 'request.released')->where('subject_id', $recordRequest->id)->first();

    expect($releasedLog)->not->toBeNull()
        ->and($releasedLog->metadata['representative_name'])->toBe('Juan Dela Cruz');
});

test('a request cannot be released before it is approved', function (string $state) {
    Storage::fake('local');

    $recordRequest = $state === 'pending'
        ? RecordRequest::factory()->create()
        : RecordRequest::factory()->{$state}()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.release.create', $recordRequest))->assertForbidden();
    $this->actingAs($staff)->post(route('requests.release.store', $recordRequest), validRelease())->assertForbidden();
})->with(['pending', 'rejected', 'cancellationRequested', 'cancelled']);

test('a request cannot be released twice', function () {
    Storage::fake('local');

    $recordRequest = RecordRequest::factory()->released()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.release.store', $recordRequest), validRelease())->assertForbidden();
});

test('the representative name is required', function () {
    Storage::fake('local');

    $recordRequest = RecordRequest::factory()->approved()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->post(route('requests.release.store', $recordRequest), [])
        ->assertSessionHasErrors('representative_name');
});

test('only staff or admin can release a document', function () {
    $recordRequest = RecordRequest::factory()->approved()->create();
    $student = User::factory()->create();

    $this->actingAs($student)->get(route('requests.release.create', $recordRequest))->assertForbidden();
    $this->actingAs($student)->post(route('requests.release.store', $recordRequest), validRelease())->assertForbidden();
});

test('guests are redirected to log in when releasing a document', function () {
    $recordRequest = RecordRequest::factory()->approved()->create();

    $this->get(route('requests.release.create', $recordRequest))->assertRedirect(route('login'));
    $this->post(route('requests.release.store', $recordRequest), validRelease())->assertRedirect(route('login'));
});
