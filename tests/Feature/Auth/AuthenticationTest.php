<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('login is recorded in the audit log', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'action' => 'auth.login']);
});

test('deactivated users can not authenticate', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email' => trans('auth.failed')]);
});

test('a user deactivated during a session is signed out on the next request', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
