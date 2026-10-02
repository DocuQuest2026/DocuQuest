<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->staff()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->staff()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('students see their student number and profile fields on the profile page', function () {
    $user = User::factory()->withStudentProfile()->create();

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee($user->studentProfile->student_no)
        ->assertSee($user->studentProfile->course);
});

test('students can update their profile and their name follows the profile', function () {
    $user = User::factory()->withStudentProfile()->create();

    $this->actingAs($user)
        ->patch('/profile', [
            'first_name' => 'Maria',
            'middle_name' => null,
            'last_name' => 'Reyes',
            'course' => 'BS Computer Science',
            'enrolment_status' => 'enrolled',
            'year_level' => 2,
            'contact_no' => '09171234567',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->name)->toBe('Maria Reyes')
        ->and($user->studentProfile->course)->toBe('BS Computer Science')
        ->and($user->studentProfile->year_level)->toBe(2)
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

test('moving to graduated clears the year level', function () {
    $user = User::factory()->withStudentProfile()->create();

    $this->actingAs($user)
        ->patch('/profile', [
            'first_name' => 'Maria',
            'last_name' => 'Reyes',
            'course' => 'BS Computer Science',
            'enrolment_status' => 'graduated',
            'year_level' => 4,
            'contact_no' => '09171234567',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors();

    expect($user->studentProfile()->first()->year_level)->toBeNull();
});

test('a student can not change their student number through the profile', function () {
    $user = User::factory()->withStudentProfile()->create();
    $original = $user->studentProfile->student_no;

    $this->actingAs($user)
        ->patch('/profile', [
            'student_no' => '9999-99999',
            'first_name' => 'Maria',
            'last_name' => 'Reyes',
            'course' => 'BS Computer Science',
            'enrolment_status' => 'enrolled',
            'year_level' => 2,
            'contact_no' => '09171234567',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors();

    expect($user->studentProfile()->first()->student_no)->toBe($original);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('staff can not delete their own account', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->delete('/profile', ['password' => 'password'])
        ->assertForbidden();

    expect($staff->fresh())->not->toBeNull();
});

test('the delete account section is hidden from staff but shown to administrators', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get('/profile')
        ->assertOk()
        ->assertDontSee('Delete Account');

    $this->actingAs(User::factory()->admin()->create())
        ->get('/profile')
        ->assertOk()
        ->assertSee('Delete Account');
});
