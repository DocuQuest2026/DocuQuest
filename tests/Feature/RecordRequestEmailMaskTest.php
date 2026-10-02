<?php

use App\Models\AuditLog;
use App\Models\RecordRequest;
use App\Models\User;

test('the requester email is masked on the request details page', function (string $role) {
    $recordRequest = RecordRequest::factory()->create(['email' => 'juan.delacruz@gmail.com']);
    $office = User::factory()->{$role}()->create();

    $this->actingAs($office)
        ->get(route('requests.show', $recordRequest))
        ->assertOk()
        ->assertSee('j***@gmail.com')
        ->assertDontSee('juan.delacruz@gmail.com')
        ->assertSee(route('requests.reveal-email', $recordRequest));
})->with(['staff', 'admin']);

test('staff and administrators can reveal the full email, which is audited', function (string $role) {
    $recordRequest = RecordRequest::factory()->create(['email' => 'juan.delacruz@gmail.com']);
    $office = User::factory()->{$role}()->create();

    $this->actingAs($office)
        ->followingRedirects()
        ->post(route('requests.reveal-email', $recordRequest))
        ->assertOk()
        ->assertSee('juan.delacruz@gmail.com');

    $log = AuditLog::where('action', 'request.email_revealed')->where('subject_id', $recordRequest->id)->first();

    expect($log)->not->toBeNull()
        ->and($log->actor_id)->toBe($office->id);
})->with(['staff', 'admin']);

test('the email is masked again on the next visit after a reveal', function () {
    $recordRequest = RecordRequest::factory()->create(['email' => 'juan.delacruz@gmail.com']);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->followingRedirects()->post(route('requests.reveal-email', $recordRequest));

    $this->actingAs($staff)->get(route('requests.show', $recordRequest))
        ->assertSee('j***@gmail.com')
        ->assertDontSee('juan.delacruz@gmail.com');
});

test('students can not reveal a requester email', function () {
    $recordRequest = RecordRequest::factory()->create();
    $student = User::factory()->create();

    $this->actingAs($student)->post(route('requests.reveal-email', $recordRequest))->assertForbidden();

    expect(AuditLog::where('action', 'request.email_revealed')->exists())->toBeFalse();
});

test('guests are redirected to log in when revealing an email', function () {
    $recordRequest = RecordRequest::factory()->create();

    $this->post(route('requests.reveal-email', $recordRequest))->assertRedirect(route('login'));
});
