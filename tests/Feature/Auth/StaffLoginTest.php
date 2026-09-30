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
    expect($user->fresh()->isOnline())->toBeTrue();
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

test('logging out immediately marks the account offline', function () {
    $staff = User::factory()->staff()->create(['last_seen_at' => now()]);

    $this->actingAs($staff)->post('/logout');

    expect($staff->fresh()->isOnline())->toBeFalse();
});

test('an authenticated request refreshes last seen, but not on every request', function () {
    $staff = User::factory()->staff()->create();
    $this->actingAs($staff)->get(route('dashboard'));

    $firstSeen = $staff->fresh()->last_seen_at;
    expect($firstSeen)->not->toBeNull();

    $this->actingAs($staff)->get(route('dashboard'));
    expect($staff->fresh()->last_seen_at)->toEqual($firstSeen);

    $this->travel(2)->minutes();
    $this->actingAs($staff)->get(route('dashboard'));
    expect($staff->fresh()->last_seen_at)->not->toEqual($firstSeen);
});

test('a user deactivated during a session is signed out on the next request', function () {
    $staff = User::factory()->staff()->inactive()->create();

    $response = $this->actingAs($staff)->get('/dashboard');

    $this->assertGuest();
    $response->assertRedirect(route('staff.login'));
    $response->assertSessionHasErrors('email');
});
