<?php

use App\Enums\DocumentType;
use App\Enums\EnrolmentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Valid input for the public record request form.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validRecordRequest(array $overrides = []): array
{
    return [
        'student_no' => '2020-00123',
        'first_name' => 'Maria',
        'middle_name' => 'Santos',
        'last_name' => 'Cruz',
        'course' => 'BS Information Technology',
        'enrolment_status' => EnrolmentStatus::Alumni->value,
        'email' => 'maria.cruz@gmail.com',
        'contact_no' => '09171234567',
        'document_type' => DocumentType::TranscriptOfRecords->value,
        'copies' => 2,
        'purpose' => 'Employment',
        ...$overrides,
    ];
}
