<?php

use App\Enums\EnrolmentStatus;
use App\Enums\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function studentRegistrationPayload(array $overrides = []): array
{
    return [
        'student_no' => '2024-00123',
        'first_name' => 'Maria',
        'middle_name' => 'Santos',
        'last_name' => 'Reyes',
        'course' => 'BS Information Technology',
        'enrolment_status' => 'enrolled',
        'year_level' => 3,
        'contact_no' => '0917 123 4567',
        'email' => 'maria@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        ...$overrides,
    ];
}

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', studentRegistrationPayload());

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration creates an unverified student account with a profile and an audit entry', function () {
    $this->post('/register', studentRegistrationPayload());

    $user = User::where('email', 'maria@example.com')->firstOrFail();

    expect($user->role)->toBe(Role::Student)
        ->and($user->is_active)->toBeTrue()
        ->and($user->name)->toBe('Maria Santos Reyes')
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->studentProfile->student_no)->toBe('2024-00123')
        ->and($user->studentProfile->year_level)->toBe(3);

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $user->id,
        'action' => 'user.registered',
        'subject_type' => $user->getMorphClass(),
        'subject_id' => $user->id,
    ]);
});

test('registration sends the email verification notification', function () {
    Notification::fake();

    $this->post('/register', studentRegistrationPayload());

    Notification::assertSentTo(User::where('email', 'maria@example.com')->firstOrFail(), VerifyEmail::class);
});

test('registration ignores a submitted role and always creates a student', function () {
    $this->post('/register', studentRegistrationPayload(['role' => 'admin', 'is_active' => false]));

    $user = User::where('email', 'maria@example.com')->firstOrFail();

    expect($user->role)->toBe(Role::Student)->and($user->is_active)->toBeTrue();
});

test('graduates register without a year level', function () {
    $this->post('/register', studentRegistrationPayload([
        'enrolment_status' => 'graduated',
        'year_level' => 4,
    ]))->assertSessionHasNoErrors();

    $profile = StudentProfile::where('student_no', '2024-00123')->firstOrFail();

    expect($profile->enrolment_status)->toBe(EnrolmentStatus::Graduated)
        ->and($profile->year_level)->toBeNull();
});

test('registration requires every mandatory field', function () {
    $this->post('/register', [])->assertSessionHasErrors([
        'student_no', 'first_name', 'last_name', 'course', 'enrolment_status', 'contact_no', 'email', 'password',
    ]);

    $this->assertGuest();
});

test('registration rejects invalid input', function (array $overrides, string $field) {
    $this->post('/register', studentRegistrationPayload($overrides))->assertSessionHasErrors($field);

    $this->assertGuest();
})->with([
    'missing year level while enrolled' => [['year_level' => null], 'year_level'],
    'year level above the maximum' => [['year_level' => 7], 'year_level'],
    'unknown enrolment status' => [['enrolment_status' => 'dropped'], 'enrolment_status'],
    'contact number with letters' => [['contact_no' => 'call me'], 'contact_no'],
    'malformed email' => [['email' => 'not-an-email'], 'email'],
    'password confirmation mismatch' => [['password_confirmation' => 'different'], 'password'],
]);

test('registration rejects a student number that is already registered', function () {
    StudentProfile::factory()->create(['student_no' => '2024-00123']);

    $this->post('/register', studentRegistrationPayload())->assertSessionHasErrors('student_no');
});

test('registration rejects an email address that is already registered', function () {
    User::factory()->create(['email' => 'maria@example.com']);

    $this->post('/register', studentRegistrationPayload())->assertSessionHasErrors('email');
});
