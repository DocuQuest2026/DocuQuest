<?php

use App\Enums\DocumentType;
use App\Enums\RequestStatus;
use App\Enums\ValidIdType;
use App\Models\RecordRequest;

test('the landing page links to the request form', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Online Request of Student Record')
        ->assertSee('Request Now')
        ->assertSee(route('record-requests.create'));
});

test('the request form can be rendered without signing in', function () {
    $this->get(route('record-requests.create'))->assertOk()->assertSee('Student number');
});

test('the request form links back to the landing page', function () {
    $this->get(route('record-requests.create'))
        ->assertOk()
        ->assertSee('Back to home');
});

test('the request form offers the configured courses to choose from', function () {
    $response = $this->get(route('record-requests.create'))->assertOk();

    foreach (config('school.courses') as $course) {
        $response->assertSee($course);
    }
});

test('the request form shows the fee of each document', function () {
    $response = $this->get(route('record-requests.create'))->assertOk();

    foreach (DocumentType::cases() as $document) {
        $response->assertSee($document->label().' — '.$document->formattedFee(), escape: false);
    }
});

test('a record request can be submitted and returns a reference number', function () {
    $response = $this->post(route('record-requests.store'), validRecordRequest());

    $response->assertRedirect(route('record-requests.create'))->assertSessionHas('reference_no');

    $recordRequest = RecordRequest::firstOrFail();
    expect($recordRequest->student_no)->toBe('2020-00123')
        ->and($recordRequest->document_type)->toBe(DocumentType::TranscriptOfRecords)
        ->and($recordRequest->status)->toBe(RequestStatus::Pending)
        ->and(session('reference_no'))->toBe($recordRequest->reference_no);
});

test('a record request is rejected when the input is invalid', function () {
    $this->post(route('record-requests.store'), validRecordRequest([
        'student_no' => '',
        'document_type' => 'diploma_of_wizardry',
        'copies' => 50,
        'course' => 'BS Underwater Basket Weaving',
    ]))->assertSessionHasErrors(['student_no', 'document_type', 'copies', 'course']);

    expect(RecordRequest::count())->toBe(0);
});

test('too many submissions from one connection are temporarily blocked', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('record-requests.store'), validRecordRequest(['student_no' => "2020-0000{$attempt}"]))
            ->assertRedirect(route('record-requests.create'))
            ->assertSessionHas('reference_no');
    }

    $this->from(route('record-requests.create'))
        ->post(route('record-requests.store'), validRecordRequest(['student_no' => '2020-99999']))
        ->assertRedirect(route('record-requests.create'))
        ->assertSessionHasErrors('throttle');

    expect(RecordRequest::count())->toBe(5);
});

test('one student number can not flood requests from different connections', function () {
    foreach (range(1, 5) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$attempt}"])
            ->post(route('record-requests.store'), validRecordRequest())
            ->assertSessionHas('reference_no');
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
        ->post(route('record-requests.store'), validRecordRequest())
        ->assertSessionHasErrors('throttle');

    expect(RecordRequest::count())->toBe(5);
});

test('only well-formed gmail addresses are accepted', function (string $email, bool $accepted) {
    $response = $this->post(route('record-requests.store'), validRecordRequest(['email' => $email]));

    $accepted
        ? $response->assertSessionHasNoErrors()
        : $response->assertSessionHasErrors('email');
})->with([
    'plain' => ['juan.delacruz@gmail.com', true],
    'uppercase is normalised' => ['Juan.DelaCruz@Gmail.com', true],
    'other provider' => ['juan.delacruz@yahoo.com', false],
    'lookalike domain' => ['juan.delacruz@gmail.co', false],
    'too short' => ['juan@gmail.com', false],
    'too long' => ['abcdefghijklmnopqrstuvwxyz12345@gmail.com', false],
    'leading period' => ['.juan.delacruz@gmail.com', false],
    'consecutive periods' => ['juan..delacruz@gmail.com', false],
    'trailing period' => ['juan.delacruz.@gmail.com', false],
    'plus alias' => ['juan.delacruz+spam@gmail.com', false],
    'underscore' => ['juan_delacruz@gmail.com', false],
]);

test('the email is stored in lowercase', function () {
    $this->post(route('record-requests.store'), validRecordRequest(['email' => ' Maria.Cruz@Gmail.com ']));

    expect(RecordRequest::firstOrFail()->email)->toBe('maria.cruz@gmail.com');
});

test('a requester can optionally name an authorized representative and their valid ID', function () {
    $this->post(route('record-requests.store'), validRecordRequest([
        'designated_representative_name' => 'Pedro Reyes',
        'designated_representative_id_type' => 'school_id',
    ]));

    $recordRequest = RecordRequest::firstOrFail();
    expect($recordRequest->designated_representative_name)->toBe('Pedro Reyes')
        ->and($recordRequest->designated_representative_id_type)->toBe(ValidIdType::SchoolId);
});

test('the authorized representative and their valid ID are optional', function () {
    $this->post(route('record-requests.store'), validRecordRequest())
        ->assertSessionDoesntHaveErrors(['designated_representative_name', 'designated_representative_id_type']);

    $recordRequest = RecordRequest::firstOrFail();
    expect($recordRequest->designated_representative_name)->toBeNull()
        ->and($recordRequest->designated_representative_id_type)->toBeNull();
});

test('a valid ID type is required once a representative is named', function () {
    $this->post(route('record-requests.store'), validRecordRequest(['designated_representative_name' => 'Pedro Reyes']))
        ->assertSessionHasErrors('designated_representative_id_type');

    expect(RecordRequest::count())->toBe(0);
});

test('the designated representative ID type must be a known value', function () {
    $this->post(route('record-requests.store'), validRecordRequest([
        'designated_representative_name' => 'Pedro Reyes',
        'designated_representative_id_type' => 'passport',
    ]))->assertSessionHasErrors('designated_representative_id_type');

    expect(RecordRequest::count())->toBe(0);
});
