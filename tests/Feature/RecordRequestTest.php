<?php

use App\Enums\DocumentType;
use App\Enums\RequestStatus;
use App\Enums\ValidIdType;
use App\Mail\RecordRequestReceived;
use App\Models\RecordRequest;

test('the landing page links to the request form', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Request your student records online')
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
        $response->assertSee($document->label())->assertSee($document->formattedFee());
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
        'documents' => ['diploma_of_wizardry' => ['copies' => 50]],
        'course' => 'BS Underwater Basket Weaving',
    ]))->assertSessionHasErrors(['student_no', 'documents', 'documents.diploma_of_wizardry.copies', 'course']);

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

test('the request form shows the reference number with a copy button once a request is submitted', function () {
    $this->withSession(['reference_no' => 'REQ-ABCD1234', 'email' => 'student@gmail.com'])
        ->get(route('record-requests.create'))
        ->assertOk()
        ->assertSee('Request Submitted')
        ->assertSee('REQ-ABCD1234')
        ->assertSee('student@gmail.com')
        ->assertSee('Copy reference number')
        ->assertSee(route('record-requests.cancel.create', ['reference_no' => 'REQ-ABCD1234']), false)
        ->assertDontSee('Student number');
});

test('the request form keeps what was typed and opens the representative section after a validation error', function () {
    $this->from(route('record-requests.create'))
        ->post(route('record-requests.store'), validRecordRequest([
            'designated_representative_name' => 'Maria Santos',
            'designated_representative_id_type' => '',
        ]))
        ->assertRedirect(route('record-requests.create'))
        ->assertSessionHasErrors('designated_representative_id_type');

    $this->get(route('record-requests.create'))
        ->assertOk()
        ->assertSee('value="Maria Santos"', false)
        ->assertSee('hasRepresentative: true', false);
});

test('the request form tells requesters how long processing takes and that an approved request can not be cancelled', function () {
    config(['school.processing_days' => 4, 'school.cancellation_window_days' => 2]);

    $this->get(route('record-requests.create'))
        ->assertOk()
        ->assertSee('The registrar needs up to 4 days to process your request, depending on the document requested.')
        ->assertSee('You can ask to cancel a pending request within 2 days. Once your request is approved, it can no longer be cancelled.');
});

test('a one-day cancellation window reads "within a day" on the form and in the confirmation email', function () {
    config(['school.cancellation_window_days' => 1]);

    $this->get(route('record-requests.create'))
        ->assertOk()
        ->assertSee('You can ask to cancel a pending request within a day. Once your request is approved, it can no longer be cancelled.')
        ->assertDontSee('1 days');

    $mail = new RecordRequestReceived(RecordRequest::factory()->create());

    expect($mail->render())->toContain('You can request cancellation within a day of submitting.');
});

test('only an 11-digit contact number is accepted', function (string $contactNo, bool $accepted) {
    $response = $this->post(route('record-requests.store'), validRecordRequest(['contact_no' => $contactNo]));

    $accepted
        ? $response->assertSessionHasNoErrors()
        : $response->assertSessionHasErrors('contact_no');
})->with([
    'eleven digits' => ['09171234567', true],
    'eleven digits starting with another number' => ['63917123456', true],
    'ten digits' => ['0917123456', false],
    'nine digits' => ['091712345', false],
    'twelve digits' => ['091712345678', false],
    'letters' => ['0917abc4567', false],
    'only letters' => ['abcdefghijk', false],
    'international prefix' => ['+6391712345', false],
    'dashes' => ['0917-123-456', false],
    'spaces inside' => ['0917 123 456', false],
    'brackets' => ['(0917)123456', false],
]);

test('stray spaces around an 11-digit contact number are ignored', function () {
    $this->post(route('record-requests.store'), validRecordRequest(['contact_no' => '  09171234567  ']))
        ->assertSessionHasNoErrors();

    expect(RecordRequest::firstOrFail()->contact_no)->toBe('09171234567');
});

test('a bad contact number shows a clear message', function () {
    $this->from(route('record-requests.create'))
        ->post(route('record-requests.store'), validRecordRequest(['contact_no' => '0917123456']))
        ->assertSessionHasErrors(['contact_no' => 'The contact number must be exactly 11 digits, numbers only (for example 09171234567).']);

    expect(RecordRequest::count())->toBe(0);
});

test('the contact number field only allows 11 digits in the browser', function () {
    $this->get(route('record-requests.create'))
        ->assertOk()
        ->assertSeeHtml('inputmode="numeric"')
        ->assertSeeHtml('minlength="11"')
        ->assertSeeHtml('pattern="[0-9]{11}"')
        ->assertSeeHtml("replace(/\D/g, '')");
});

test('names can not contain numbers', function (string $field, string $value) {
    $this->from(route('record-requests.create'))
        ->post(route('record-requests.store'), validRecordRequest([$field => $value]))
        ->assertSessionHasErrors($field);

    expect(RecordRequest::count())->toBe(0);
})->with([
    'first name with a digit' => ['first_name', 'Maria2'],
    'first name only digits' => ['first_name', '12345'],
    'middle name with a digit' => ['middle_name', 'Santos3'],
    'last name with a digit' => ['last_name', '1Cruz'],
    'last name with a digit in the middle' => ['last_name', 'De la Cr8uz'],
    'superscript number' => ['first_name', 'Maria²'],
    'arabic-indic digit' => ['last_name', 'Cruz٣'],
]);

test('names with accents, spaces, hyphens, apostrophes and periods are accepted', function (string $name) {
    $this->post(route('record-requests.store'), validRecordRequest([
        'first_name' => $name,
        'middle_name' => $name,
        'last_name' => $name,
    ]))->assertSessionHasNoErrors();
})->with([
    'plain' => ['Maria'],
    'accented' => ['Peña'],
    'two words' => ['De la Cruz'],
    'hyphenated' => ['Anne-Marie'],
    'apostrophe' => ["O'Brien"],
    'period' => ['Ma. Cristina'],
]);

test('the middle name stays optional', function () {
    $this->post(route('record-requests.store'), validRecordRequest(['middle_name' => '']))
        ->assertSessionHasNoErrors();
});

test('a name with a number shows a clear message', function () {
    $this->from(route('record-requests.create'))
        ->post(route('record-requests.store'), validRecordRequest(['first_name' => 'Maria2', 'last_name' => 'Cruz9']))
        ->assertSessionHasErrors([
            'first_name' => 'The first name must not contain numbers.',
            'last_name' => 'The last name must not contain numbers.',
        ]);
});

test('the name fields strip numbers as people type', function () {
    $html = $this->get(route('record-requests.create'))->assertOk()->getContent();

    expect(substr_count($html, "replace(/\p{N}/gu, '')"))->toBe(3);
});

test('the submitted screen reminds the requester that an approved request can not be cancelled, as step 2', function () {
    config(['school.cancellation_window_days' => 1]);

    $html = $this->withSession(['reference_no' => 'REQ-ABCD1234', 'email' => 'student@gmail.com'])
        ->get(route('record-requests.create'))
        ->assertOk()
        ->assertSeeInOrder([
            'The registrar reviews your request',
            'Reminder: no cancelling once approved',
            'Once your request is approved, you can no longer cancel it. Until then, you can still cancel it within a day of submitting.',
            'You are contacted when it is ready',
            'Claim your document',
        ])
        ->assertDontSee('Request submitted')
        ->getContent();

    $steps = preg_match_all('/<li class="flex gap-3[^"]*">\s*<span[^>]*>(\d)<\/span>/', $html, $matches);

    expect($steps)->toBe(4)
        ->and($matches[1])->toBe(['1', '2', '3', '4']);
});

test('the request form shows a review pop-up in the middle of the screen before submitting', function () {
    config(['school.processing_days' => 3, 'school.cancellation_window_days' => 1]);

    $this->get(route('record-requests.create'))
        ->assertOk()
        ->assertSee('Review your request')
        ->assertSee('Please check your details before you submit.')
        ->assertSee('Confirm and submit')
        ->assertSee('Edit details')
        ->assertSee('The registrar needs up to 3 days to process your request, depending on the document requested.')
        ->assertSee('You can cancel a pending request within a day. Once it is approved, it can no longer be cancelled.')
        ->assertSeeHtml('role="dialog"')
        ->assertSeeHtml('items-center justify-center')
        ->assertSeeHtml('x-on:submit.prevent="openReview($event.target)"');
});

test('the review pop-up does not replace the real form: it still posts to the store route with a csrf token', function () {
    $this->get(route('record-requests.create'))
        ->assertOk()
        ->assertSeeHtml('action="'.route('record-requests.store').'"')
        ->assertSeeHtml('name="_token"');
});
