<?php

use App\Enums\DocumentType;
use App\Enums\RequestStatus;
use App\Mail\RecordRequestReceived;
use App\Models\RecordRequest;
use Illuminate\Support\Facades\Mail;

/**
 * Every document type, one copy each, in the shape the request form posts them.
 *
 * @return array<string, array{copies: int}>
 */
function everyDocument(): array
{
    return collect(DocumentType::cases())
        ->mapWithKeys(fn (DocumentType $type): array => [$type->value => ['copies' => 1]])
        ->all();
}

test('a student can request several documents at once and each becomes its own request', function () {
    Mail::fake();

    $response = $this->post(route('record-requests.store'), validRecordRequest([
        'documents' => [
            DocumentType::CertificateOfGoodMoral->value => ['copies' => 3],
            DocumentType::TranscriptOfRecords->value => ['copies' => 1],
            DocumentType::CertificateOfEnrolment->value => ['copies' => 2],
        ],
    ]))->assertRedirect(route('record-requests.create'));

    $created = RecordRequest::orderBy('id')->get();

    expect($created)->toHaveCount(3)
        ->and($created->pluck('document_type')->all())->toBe([
            DocumentType::TranscriptOfRecords,
            DocumentType::CertificateOfEnrolment,
            DocumentType::CertificateOfGoodMoral,
        ])
        ->and($created->pluck('copies')->all())->toBe([1, 2, 3])
        ->and($created->pluck('reference_no')->unique())->toHaveCount(3)
        ->and($created->pluck('student_no')->unique()->all())->toBe(['2020-00123'])
        ->and($created->pluck('email')->unique()->all())->toBe(['maria.cruz@gmail.com'])
        ->and($created->pluck('status')->unique()->all())->toBe([RequestStatus::Pending]);

    $response->assertSessionHas('reference_no', $created->first()->reference_no)
        ->assertSessionHas('submitted_requests', [
            ['reference_no' => $created[0]->reference_no, 'document' => 'Transcript of Records', 'copies' => 1],
            ['reference_no' => $created[1]->reference_no, 'document' => 'Certificate of Enrolment', 'copies' => 2],
            ['reference_no' => $created[2]->reference_no, 'document' => 'Certificate of Good Moral Character', 'copies' => 3],
        ]);
});

test('requesting several documents sends one email listing every reference number and cancel link', function () {
    Mail::fake();

    $this->post(route('record-requests.store'), validRecordRequest([
        'documents' => [
            DocumentType::TranscriptOfRecords->value => ['copies' => 1],
            DocumentType::CertificateOfEnrolment->value => ['copies' => 2],
        ],
    ]));

    $created = RecordRequest::orderBy('id')->get();

    Mail::assertSent(RecordRequestReceived::class, 1);
    Mail::assertSent(RecordRequestReceived::class, function (RecordRequestReceived $mail) use ($created) {
        $html = $mail->render();

        return $mail->hasTo('maria.cruz@gmail.com')
            && $mail->hasSubject('Your reference numbers: '.$created->pluck('reference_no')->implode(', '))
            && $created->every(fn (RecordRequest $recordRequest) => str_contains($html, $recordRequest->reference_no)
                && str_contains($html, route('record-requests.cancel.create', ['reference_no' => $recordRequest->reference_no]))
                && str_contains($html, $recordRequest->document_type->label()))
            && str_contains($html, '₱250.00');
    });
});

test('a single document still sends the original single-reference email', function () {
    $mail = new RecordRequestReceived(RecordRequest::factory()->create());

    expect($mail->render())->toContain('Your reference number:')
        ->not->toContain('reference numbers');
});

test('at least one valid document must be chosen', function (array $documents, string $errorKey) {
    $this->post(route('record-requests.store'), validRecordRequest(['documents' => $documents]))
        ->assertSessionHasErrors($errorKey);

    expect(RecordRequest::count())->toBe(0);
})->with([
    'none chosen' => [[], 'documents'],
    'unknown document' => [['diploma_of_wizardry' => ['copies' => 1]], 'documents'],
    'known and unknown mixed' => [[DocumentType::TranscriptOfRecords->value => ['copies' => 1], 'made_up' => ['copies' => 1]], 'documents'],
    'zero copies' => [[DocumentType::TranscriptOfRecords->value => ['copies' => 0]], 'documents.transcript_of_records.copies'],
    'too many copies' => [[DocumentType::TranscriptOfRecords->value => ['copies' => 11]], 'documents.transcript_of_records.copies'],
    'copies missing' => [[DocumentType::TranscriptOfRecords->value => []], 'documents.transcript_of_records.copies'],
    'copies not a number' => [[DocumentType::TranscriptOfRecords->value => ['copies' => 'many']], 'documents.transcript_of_records.copies'],
    'a document with a bad count rejects the whole request' => [[
        DocumentType::TranscriptOfRecords->value => ['copies' => 1],
        DocumentType::CertificateOfEnrolment->value => ['copies' => 99],
    ], 'documents.certificate_of_enrolment.copies'],
]);

test('every document type can be chosen together', function () {
    $this->post(route('record-requests.store'), validRecordRequest(['documents' => everyDocument()]))
        ->assertSessionHasNoErrors();

    expect(RecordRequest::count())->toBe(count(DocumentType::cases()));
});

test('requesting several documents counts as one submission for the rate limit', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('record-requests.store'), validRecordRequest(['documents' => everyDocument()]))->assertSessionHasNoErrors();
    }

    $this->post(route('record-requests.store'), validRecordRequest(['documents' => everyDocument()]))->assertSessionHasErrors('throttle');

    expect(RecordRequest::count())->toBe(5 * count(DocumentType::cases()));
});

test('the request form lets the student tick several documents, each with its own copies', function () {
    $response = $this->get(route('record-requests.create'))->assertOk();

    foreach (DocumentType::cases() as $document) {
        $response->assertSeeHtml('name="documents['.$document->value.'][copies]"')
            ->assertSeeHtml('id="document_'.$document->value.'"');
    }

    $response->assertSee('Tick every document you need. You can choose more than one.')
        ->assertDontSee('Select a document');
});

test('the request form remembers the chosen documents and their copies after a validation error', function () {
    $this->from(route('record-requests.create'))
        ->post(route('record-requests.store'), validRecordRequest([
            'first_name' => 'Maria2',
            'documents' => [
                DocumentType::CertificateOfGraduation->value => ['copies' => 4],
                DocumentType::CertificateOfGoodMoral->value => ['copies' => 2],
            ],
        ]))
        ->assertSessionHasErrors('first_name');

    $html = $this->get(route('record-requests.create'))->assertOk()->getContent();

    // The page stores the chosen documents as JSON with encoded quotes (a backslash, then u0022).
    $quote = chr(92).'u0022';

    expect($html)->toContain('value="4"')
        ->and($html)->toContain($quote.'certificate_of_graduation'.$quote.':true')
        ->and($html)->toContain($quote.'certificate_of_good_moral'.$quote.':true')
        ->and($html)->toContain($quote.'transcript_of_records'.$quote.':false');
});

test('the submitted screen lists every reference number when several documents were requested', function () {
    $this->withSession([
        'reference_no' => 'REQ-AAAA1111',
        'email' => 'student@gmail.com',
        'submitted_requests' => [
            ['reference_no' => 'REQ-AAAA1111', 'document' => 'Transcript of Records', 'copies' => 1],
            ['reference_no' => 'REQ-BBBB2222', 'document' => 'Certificate of Enrolment', 'copies' => 2],
        ],
    ])->get(route('record-requests.create'))
        ->assertOk()
        ->assertSee('Request Submitted')
        ->assertSee('We sent your 2 reference numbers to student@gmail.com')
        ->assertSee('REQ-AAAA1111')
        ->assertSee('REQ-BBBB2222')
        ->assertSee('Transcript of Records')
        ->assertSee('Certificate of Enrolment')
        ->assertSee(route('record-requests.cancel.create', ['reference_no' => 'REQ-AAAA1111']), false)
        ->assertSee(route('record-requests.cancel.create', ['reference_no' => 'REQ-BBBB2222']), false)
        ->assertSee('Reminder: no cancelling once approved');
});
