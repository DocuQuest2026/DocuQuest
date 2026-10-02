<?php

use App\Models\User;

test('the staff login describes the registrar workspace, not the student side', function () {
    $this->get(config('auth.staff_login_path'))
        ->assertOk()
        ->assertSee('Staff sign in')
        ->assertSee('Registrar staff and administrators only.')
        ->assertSee('Registrar workspace')
        ->assertSee('Every record request, in one place.')
        ->assertSee('Review and approve requests')
        ->assertSee('Release documents')
        ->assertSee('Handle cancellations')
        ->assertDontSee('track your enrolment')
        ->assertDontSee('manage your student records');
});

test('the staff login offers the usual fields, a password toggle and the useful links', function () {
    $this->get(config('auth.staff_login_path'))
        ->assertOk()
        ->assertSeeHtml('name="email"')
        ->assertSeeHtml('autocomplete="username"')
        ->assertSeeHtml('name="password"')
        ->assertSeeHtml('autocomplete="current-password"')
        ->assertSeeHtml('name="remember"')
        ->assertSeeHtml('aria-pressed')
        ->assertSee('Forgot your password?')
        ->assertSee(route('password.request'), false)
        ->assertSee('Not staff?')
        ->assertSee('Back to home')
        ->assertDontSee('Go to the student page');
});

test('the staff login still posts to the login route with a csrf token', function () {
    $this->get(config('auth.staff_login_path'))
        ->assertOk()
        ->assertSeeHtml('action="'.route('staff.login').'"')
        ->assertSeeHtml('name="_token"');
});

test('a wrong password shows the error and keeps the email typed', function () {
    $staff = User::factory()->staff()->create();

    $this->followingRedirects()
        ->from(config('auth.staff_login_path'))
        ->post(config('auth.staff_login_path'), ['email' => $staff->email, 'password' => 'not-the-password'])
        ->assertOk()
        ->assertSee('These credentials do not match our records.')
        ->assertSeeHtml('value="'.$staff->email.'"');

    $this->assertGuest();
});

test('the other staff account screens share the same registrar workspace layout', function (string $routeName) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSee('Registrar workspace')
        ->assertSee('Every record request, in one place.');
})->with(['password.request']);
