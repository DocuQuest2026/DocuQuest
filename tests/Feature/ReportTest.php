<?php

use App\Enums\RequestStatus;
use App\Models\AuditLog;
use App\Models\RecordRequest;
use App\Models\User;
use App\Services\RequestReport;
use Illuminate\Support\Carbon;

test('staff and admins can open the reports page', function (string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)->get(route('reports.index'))->assertOk()->assertSee('Reports')->assertSee('Download PDF');
})->with(['staff', 'admin']);

test('guests cannot open or download reports', function () {
    $this->get(route('reports.index'))->assertRedirect(route('login'));
    $this->get(route('reports.export'))->assertRedirect(route('login'));
});

test('the report counts requests, copies, fees and urgent requests in the period', function () {
    $staff = User::factory()->staff()->create();
    $enrolment = documentType('certificate_of_enrolment');
    $transcript = documentType('transcript_of_records');

    RecordRequest::factory()->urgent()->create(['document_type_id' => $enrolment->id, 'copies' => 2, 'created_at' => '2026-10-05 09:00:00']);
    RecordRequest::factory()->rejected()->create(['document_type_id' => $transcript->id, 'copies' => 1, 'created_at' => '2026-10-06 09:00:00']);
    RecordRequest::factory()->create(['document_type_id' => $enrolment->id, 'created_at' => '2026-09-01 09:00:00']);

    $response = $this->actingAs($staff)->get(route('reports.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
        ->assertOk()
        ->assertViewHas('totals', fn (array $totals): bool => $totals['total'] === 2
            && $totals['urgent'] === 1
            && $totals['copies'] === 3
            && $totals['fees'] === 250)
        ->assertViewHas('byStatus', fn (array $byStatus): bool => $byStatus['pending'] === 1 && $byStatus['rejected'] === 1 && $byStatus['released'] === 0);

    $response->assertSee('Certificate of Enrolment')->assertSee('Transcript of Records');
});

test('the report can be limited to one status and one document', function () {
    $staff = User::factory()->staff()->create();
    $enrolment = documentType('certificate_of_enrolment');

    RecordRequest::factory()->create(['document_type_id' => $enrolment->id, 'created_at' => '2026-10-05 09:00:00']);
    RecordRequest::factory()->rejected()->create(['document_type_id' => $enrolment->id, 'created_at' => '2026-10-05 09:00:00']);
    RecordRequest::factory()->create(['document_type_id' => documentType('transcript_of_records')->id, 'created_at' => '2026-10-05 09:00:00']);

    $this->actingAs($staff)->get(route('reports.index', [
        'from' => '2026-10-01',
        'to' => '2026-10-31',
        'status' => 'pending',
        'document_type_id' => $enrolment->id,
    ]))->assertViewHas('totals', fn (array $totals): bool => $totals['total'] === 1);
});

test('archived requests are included in the report', function () {
    $staff = User::factory()->staff()->create();
    RecordRequest::factory()->rejected()->create(['created_at' => '2026-10-05 09:00:00'])->delete();

    $this->actingAs($staff)->get(route('reports.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
        ->assertViewHas('totals', fn (array $totals): bool => $totals['total'] === 1);
});

test('the average processing time is measured from submission to release', function () {
    $staff = User::factory()->staff()->create();

    $released = RecordRequest::factory()->released()->create(['created_at' => '2026-10-05 09:00:00']);
    $released->release->update(['released_at' => '2026-10-07 09:00:00']);

    $this->actingAs($staff)->get(route('reports.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
        ->assertViewHas('totals', fn (array $totals): bool => $totals['averageProcessingDays'] === 2.0);
});

test('the report asks for a valid date range', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('reports.index', ['from' => '2026-10-31', 'to' => '2026-10-01']))->assertSessionHasErrors('to');
    $this->actingAs($staff)->get(route('reports.index', ['status' => 'nonsense']))->assertSessionHasErrors('status');
});

test('the report downloads as a PDF file and the download is audited', function () {
    $staff = User::factory()->staff()->create();
    RecordRequest::factory()->create(['created_at' => '2026-10-05 09:00:00']);

    $response = $this->actingAs($staff)->get(route('reports.export', ['from' => '2026-10-01', 'to' => '2026-10-31']))
        ->assertOk()
        ->assertDownload('docuquest-report-2026-10-01-to-2026-10-31.pdf');

    expect($response->baseResponse->headers->get('content-type'))->toBe('application/pdf')
        ->and(str_starts_with($response->getContent(), '%PDF'))->toBeTrue();

    expect(AuditLog::where('action', 'report.exported')->where('actor_id', $staff->id)->exists())->toBeTrue();
});

test('the PDF lists the summary and each request in the period', function () {
    $recordRequest = RecordRequest::factory()->create(['first_name' => 'Maria', 'created_at' => '2026-10-05 09:00:00']);
    RecordRequest::factory()->create(['created_at' => '2026-09-01 09:00:00']);

    $report = new RequestReport(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

    $html = view('pdf.report', [
        'report' => $report,
        'totals' => $report->totals(),
        'byStatus' => $report->byStatus(),
        'byDocument' => $report->byDocument(),
        'statuses' => RequestStatus::cases(),
        'requests' => $report->requests()->with('release')->get(),
        'truncated' => false,
        'requestLimit' => 1000,
        'documentName' => null,
        'generatedBy' => 'Registrar',
    ])->render();

    expect($html)->toContain('Requests submitted from Oct 1, 2026 to Oct 31, 2026.')
        ->and($html)->toContain($recordRequest->reference_no)
        ->and($html)->toContain('Maria')
        ->and(substr_count($html, 'REQ-'))->toBe(1);
});

test('the report can not start before the system did', function () {
    config(['school.first_request_month' => '2026-09']);
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('reports.index', ['from' => '2025-12-01', 'to' => '2026-10-01']))
        ->assertSessionHasErrors('from');

    $this->actingAs($staff)->get(route('reports.index', ['from' => '2026-09-01', 'to' => '2026-10-01']))
        ->assertSessionHasNoErrors();

    $this->actingAs($staff)->get(route('reports.index'))
        ->assertSee('min="2026-09-01"', false);
});
