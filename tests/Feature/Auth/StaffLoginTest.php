<?php

use App\Models\User;

test('staff login screen can be rendered at the configured path', function () {
    $this->get(config('auth.staff_login_path'))->assertOk();
});

test('staff and administrators can sign in through the staff login', function (string $state) {
    $user = User::factory()->{$state}()->create();

    $this->post(config('auth.staff_login_path'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'action' => 'auth.login']);
})->with(['staff', 'admin']);

test('students can not sign in through the staff login', function () {
    $student = User::factory()->create();

    $this->post(config('auth.staff_login_path'), ['email' => $student->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => trans('auth.failed')]);

    $this->assertGuest();
});

test('deactivated staff can not sign in through the staff login', function () {
    $staff = User::factory()->staff()->inactive()->create();

    $this->post(config('auth.staff_login_path'), ['email' => $staff->email, 'password' => 'password']);

    $this->assertGuest();
});

test('staff can logout', function () {
    $staff = User::factory()->staff()->create();

    $response = $this->actingAs($staff)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('a user deactivated during a session is signed out on the next request', function () {
    $staff = User::factory()->staff()->inactive()->create();

    $response = $this->actingAs($staff)->get('/dashboard');

    $this->assertGuest();
    $response->assertRedirect(route('staff.login'));
    $response->assertSessionHasErrors('email');
});
