<?php

use App\Models\AuditLog;
use App\Models\DocumentType;
use App\Models\RecordRequest;
use App\Models\User;

test('an admin can see the documents with their fees and processing times', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.document-types.index'))
        ->assertOk()
        ->assertSee('Transcript of Records')
        ->assertSee('₱150.00')
        ->assertSee('5 working days');
});

test('staff and guests cannot manage documents', function () {
    $staff = User::factory()->staff()->create();
    $documentType = documentType('transcript_of_records');

    $this->actingAs($staff)->get(route('admin.document-types.index'))->assertForbidden();
    $this->actingAs($staff)->post(route('admin.document-types.store'), ['name' => 'Diploma', 'fee' => 10, 'processing_days' => 1])->assertForbidden();
    $this->actingAs($staff)->put(route('admin.document-types.update', $documentType), ['name' => 'X', 'fee' => 1, 'processing_days' => 1])->assertForbidden();

    auth()->logout();

    $this->get(route('admin.document-types.index'))->assertRedirect(route('login'));
    expect($documentType->fresh()->fee)->toBe(150);
});

test('an admin can add a document and it appears on the request form', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.document-types.store'), [
        'name' => 'Diploma Copy',
        'fee' => 250,
        'processing_days' => 4,
        'is_active' => '1',
    ])->assertRedirect(route('admin.document-types.index'));

    $documentType = DocumentType::where('name', 'Diploma Copy')->firstOrFail();

    expect($documentType->key)->toBe('diploma_copy')
        ->and($documentType->fee)->toBe(250)
        ->and($documentType->processing_days)->toBe(4)
        ->and($documentType->is_active)->toBeTrue();
    expect(AuditLog::where('action', 'document_type.created')->where('subject_id', $documentType->id)->exists())->toBeTrue();

    auth()->logout();

    $this->get(route('record-requests.create'))->assertOk()->assertSee('Diploma Copy')->assertSee('₱250.00');
    $this->get('/')->assertOk()->assertSee('Diploma Copy');
});

test('a new document can be requested', function () {
    $documentType = DocumentType::factory()->create(['name' => 'Diploma Copy', 'fee' => 250]);

    $this->post(route('record-requests.store'), validRecordRequest(['documents' => [$documentType->key => ['copies' => 2]]]))
        ->assertSessionHasNoErrors();

    $recordRequest = RecordRequest::firstOrFail();

    expect($recordRequest->document_type_id)->toBe($documentType->id)
        ->and($recordRequest->fee_per_copy)->toBe(250)
        ->and($recordRequest->totalFee())->toBe(500);
});

test('a document that is not offered cannot be requested and is hidden from the form', function () {
    $documentType = DocumentType::factory()->inactive()->create(['name' => 'Retired Certificate']);

    $this->get(route('record-requests.create'))->assertOk()->assertDontSee('Retired Certificate');

    $this->post(route('record-requests.store'), validRecordRequest(['documents' => [$documentType->key => ['copies' => 1]]]))
        ->assertSessionHasErrors('documents');

    expect(RecordRequest::count())->toBe(0);
});

test('an admin can change a fee and processing time, and the change is audited', function () {
    $admin = User::factory()->admin()->create();
    $documentType = documentType('certificate_of_enrolment');

    $this->actingAs($admin)->put(route('admin.document-types.update', $documentType), [
        'name' => 'Certificate of Enrolment',
        'fee' => 75,
        'processing_days' => 2,
        'is_active' => '1',
    ])->assertRedirect(route('admin.document-types.index'));

    $documentType->refresh();

    expect($documentType->fee)->toBe(75)->and($documentType->processing_days)->toBe(2);

    $log = AuditLog::where('action', 'document_type.updated')->where('subject_id', $documentType->id)->first();
    expect($log->metadata['fee'])->toEqual(['from' => 50, 'to' => 75]);
});

test('requests already submitted keep the fee they were submitted with', function () {
    $documentType = documentType('certificate_of_enrolment');
    $recordRequest = RecordRequest::factory()->create(['document_type_id' => $documentType->id, 'copies' => 2]);

    $documentType->update(['fee' => 500]);

    expect($recordRequest->fresh()->fee_per_copy)->toBe(50)
        ->and($recordRequest->fresh()->totalFee())->toBe(100);

    $later = RecordRequest::factory()->create(['document_type_id' => $documentType->id, 'copies' => 2]);

    expect($later->totalFee())->toBe(1000);
});

test('an admin can switch a document off, and existing requests still show its name', function () {
    $admin = User::factory()->admin()->create();
    $documentType = documentType('certificate_of_good_moral');
    $recordRequest = RecordRequest::factory()->create(['document_type_id' => $documentType->id]);

    $this->actingAs($admin)->put(route('admin.document-types.update', $documentType), [
        'name' => $documentType->name,
        'fee' => $documentType->fee,
        'processing_days' => $documentType->processing_days,
        'is_active' => '0',
    ])->assertRedirect();

    expect($documentType->fresh()->is_active)->toBeFalse()
        ->and($recordRequest->fresh()->documentType->name)->toBe($documentType->name);

    $this->get(route('record-requests.create'))->assertDontSee('Certificate of Good Moral Character');
});

test('document details are validated', function (array $input, string $field) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.document-types.store'), $input)->assertSessionHasErrors($field);

    expect(DocumentType::count())->toBe(5);
})->with([
    'missing name' => [['fee' => 10, 'processing_days' => 1], 'name'],
    'duplicate name' => [['name' => 'Transcript of Records', 'fee' => 10, 'processing_days' => 1], 'name'],
    'negative fee' => [['name' => 'New', 'fee' => -1, 'processing_days' => 1], 'fee'],
    'fee not a number' => [['name' => 'New', 'fee' => 'free', 'processing_days' => 1], 'fee'],
    'zero processing days' => [['name' => 'New', 'fee' => 10, 'processing_days' => 0], 'processing_days'],
]);

test('documents with the same name get distinct keys', function () {
    $first = DocumentType::factory()->create(['name' => 'Good Standing']);
    $second = DocumentType::factory()->create(['name' => 'Good-Standing']);

    expect($first->key)->toBe('good_standing')->and($second->key)->toBe('good_standing_2');
});
