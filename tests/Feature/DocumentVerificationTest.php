<?php

use App\Models\DocumentRelease;
use App\Models\RecordRequest;

test('the verification link shows the reference number, document type, requester name, and release date', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $release = $recordRequest->release()->first();

    $this->get($release->verificationUrl())
        ->assertOk()
        ->assertSee($recordRequest->reference_no)
        ->assertSee($recordRequest->document_type->label())
        ->assertSee($recordRequest->fullName())
        ->assertSee($release->released_at->format('M j, Y'));
});

test('the verification page does not leak sensitive requester or representative details', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $release = $recordRequest->release()->first();

    $this->get($release->verificationUrl())
        ->assertOk()
        ->assertDontSee($recordRequest->email)
        ->assertDontSee($recordRequest->contact_no)
        ->assertDontSee($recordRequest->student_no)
        ->assertDontSee($recordRequest->purpose)
        ->assertDontSee($release->representative_name);
});

test('verification links that are unsigned, tampered with, or expired are rejected', function () {
    $recordRequest = RecordRequest::factory()->released()->create();
    $release = $recordRequest->release()->first();

    $this->get(route('document-verification.show', $release))->assertForbidden();
    $this->get($release->verificationUrl().'&tampered=1')->assertForbidden();

    $url = $release->verificationUrl();
    $this->travel(config('school.document_verification_ttl_years') + 1)->years();
    $this->get($url)->assertForbidden();
});

test('an unknown verification token is not found', function () {
    $bogus = new DocumentRelease(['verification_token' => 'does-not-exist']);

    $this->get($bogus->verificationUrl())->assertNotFound();
});
