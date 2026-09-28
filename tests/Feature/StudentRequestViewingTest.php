<?php

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
        ->assertSee($recordRequest->email);
})->with(['staff', 'admin']);

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
