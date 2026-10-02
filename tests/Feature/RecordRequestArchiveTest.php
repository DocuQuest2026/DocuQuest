<?php

use App\Models\AuditLog;
use App\Models\RecordRequest;
use App\Models\User;

test('staff and administrators can archive a request', function (string $role) {
    $recordRequest = RecordRequest::factory()->rejected()->create();
    $office = User::factory()->{$role}()->create();

    $this->actingAs($office)
        ->delete(route('requests.destroy', $recordRequest))
        ->assertRedirect(route('requests.index'));

    expect(RecordRequest::find($recordRequest->id))->toBeNull()
        ->and(RecordRequest::withTrashed()->find($recordRequest->id))->not->toBeNull();
})->with(['staff', 'admin']);

test('a request can be archived once it is no longer pending or approved', function (string $state) {
    $recordRequest = RecordRequest::factory()->{$state}()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->delete(route('requests.destroy', $recordRequest))
        ->assertRedirect(route('requests.index'));

    expect(RecordRequest::find($recordRequest->id))->toBeNull();
})->with(['rejected', 'released', 'cancellationRequested', 'cancelled']);

test('a pending or approved request can not be archived and shows no archive button', function (?string $state) {
    $recordRequest = $state ? RecordRequest::factory()->{$state}()->create() : RecordRequest::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.show', $recordRequest))->assertDontSee(__('Archive request'));
    $this->actingAs($staff)->delete(route('requests.destroy', $recordRequest))->assertForbidden();

    expect(RecordRequest::find($recordRequest->id))->not->toBeNull();
})->with([null, 'approved']);

test('an archived request no longer appears in the requests list', function () {
    $recordRequest = RecordRequest::factory()->rejected()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->delete(route('requests.destroy', $recordRequest));

    $this->actingAs($staff)->get(route('requests.index'))->assertDontSee($recordRequest->reference_no);
});

test('archiving a request logs a archived audit entry', function () {
    $recordRequest = RecordRequest::factory()->rejected()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->delete(route('requests.destroy', $recordRequest));

    $log = AuditLog::where('action', 'request.archived')->where('subject_id', $recordRequest->id)->first();

    expect($log)->not->toBeNull()->and($log->actor_id)->toBe($staff->id);
});

test('archiving a released request does not break its public verification link', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $release = $recordRequest->release()->first();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->delete(route('requests.destroy', $recordRequest));

    $this->get($release->verificationUrl())
        ->assertOk()
        ->assertSee($recordRequest->reference_no);
});

test('students can not archive a request', function () {
    $recordRequest = RecordRequest::factory()->create();
    $student = User::factory()->create();

    $this->actingAs($student)->delete(route('requests.destroy', $recordRequest))->assertForbidden();
});

test('guests are redirected to log in when archiving a request', function () {
    $recordRequest = RecordRequest::factory()->create();

    $this->delete(route('requests.destroy', $recordRequest))->assertRedirect(route('login'));
});

test('staff can view the details of an archived request', function () {
    $recordRequest = RecordRequest::factory()->create();
    $recordRequest->delete();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.show', $recordRequest))
        ->assertOk()
        ->assertSee($recordRequest->reference_no)
        ->assertSee(__('This request was archived on :date.', ['date' => $recordRequest->deleted_at->format('M j, Y g:i A')]));
});

test('the archived tab on the requests list shows only deleted requests', function () {
    $deleted = RecordRequest::factory()->create();
    $deleted->delete();
    $active = RecordRequest::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('requests.index', ['status' => 'archived']))
        ->assertOk()
        ->assertSee($deleted->reference_no)
        ->assertDontSee($active->reference_no);
});

test('staff and administrators can restore an archived request', function (string $role) {
    $recordRequest = RecordRequest::factory()->create();
    $recordRequest->delete();
    $office = User::factory()->{$role}()->create();

    $this->actingAs($office)
        ->post(route('requests.restore', $recordRequest))
        ->assertRedirect(route('requests.show', $recordRequest));

    expect(RecordRequest::find($recordRequest->id))->not->toBeNull();
})->with(['staff', 'admin']);

test('a restored request reappears in the requests list', function () {
    $recordRequest = RecordRequest::factory()->create();
    $recordRequest->delete();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.restore', $recordRequest));

    $this->actingAs($staff)->get(route('requests.index'))->assertSee($recordRequest->reference_no);
});

test('restoring a request logs a restored audit entry', function () {
    $recordRequest = RecordRequest::factory()->create();
    $recordRequest->delete();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.restore', $recordRequest));

    $log = AuditLog::where('action', 'request.restored')->where('subject_id', $recordRequest->id)->first();

    expect($log)->not->toBeNull()->and($log->actor_id)->toBe($staff->id);
});

test('students can not restore an archived request', function () {
    $recordRequest = RecordRequest::factory()->create();
    $recordRequest->delete();
    $student = User::factory()->create();

    $this->actingAs($student)->post(route('requests.restore', $recordRequest))->assertForbidden();
});

test('guests are redirected to log in when restoring a request', function () {
    $recordRequest = RecordRequest::factory()->create();
    $recordRequest->delete();

    $this->post(route('requests.restore', $recordRequest))->assertRedirect(route('login'));
});

test('an active request cannot be restored and shows no restore action', function () {
    $recordRequest = RecordRequest::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('requests.restore', $recordRequest))->assertForbidden();

    $this->actingAs($staff)->get(route('requests.index'))->assertDontSee(__('Restore'));
    $this->actingAs($staff)->get(route('requests.show', $recordRequest))->assertDontSee(__('Restore request'));
});
