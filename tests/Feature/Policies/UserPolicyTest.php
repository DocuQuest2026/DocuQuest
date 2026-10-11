<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\QueryException;

test('only administrators may list and create office accounts', function (Role $role, bool $allowed) {
    $user = User::factory()->make(['role' => $role]);

    expect($user->can('viewAny', User::class))->toBe($allowed)
        ->and($user->can('create', User::class))->toBe($allowed);
})->with([
    'student' => [Role::Student, false],
    'staff' => [Role::Staff, false],
    'admin' => [Role::Admin, true],
]);

test('administrators may update office accounts but not student accounts', function (Role $targetRole, bool $allowed) {
    $admin = User::factory()->admin()->make();
    $target = User::factory()->make(['role' => $targetRole]);

    expect($admin->can('update', $target))->toBe($allowed);
})->with([
    'staff account' => [Role::Staff, true],
    'admin account' => [Role::Admin, true],
    'student account' => [Role::Student, false],
]);

test('staff may not update any account', function () {
    $staff = User::factory()->staff()->make();
    $target = User::factory()->staff()->make();

    expect($staff->can('update', $target))->toBeFalse();
});

test('only administrators may reset any account\'s password, including a student\'s', function (Role $role, bool $allowed) {
    $user = User::factory()->make(['role' => $role]);
    $target = User::factory()->make(['role' => Role::Student]);

    expect($user->can('resetPassword', $target))->toBe($allowed);
})->with([
    'student' => [Role::Student, false],
    'staff' => [Role::Staff, false],
    'admin' => [Role::Admin, true],
]);

test('no one else may edit, delete, restore or reset the password of the super administrator', function () {
    $admin = User::factory()->admin()->make(['id' => 1]);
    $superAdmin = User::factory()->superAdmin()->make(['id' => 2]);

    expect($admin->can('update', $superAdmin))->toBeFalse()
        ->and($admin->can('delete', $superAdmin))->toBeFalse()
        ->and($admin->can('resetPassword', $superAdmin))->toBeFalse();
});

test('the super administrator may still manage other accounts and change their own password', function () {
    $superAdmin = User::factory()->superAdmin()->make(['id' => 1]);
    $staff = User::factory()->staff()->make(['id' => 2]);

    expect($superAdmin->can('create', User::class))->toBeTrue()
        ->and($superAdmin->can('update', $staff))->toBeTrue()
        ->and($superAdmin->can('delete', $staff))->toBeTrue()
        ->and($superAdmin->can('resetPassword', $superAdmin))->toBeTrue();
});

test('a trashed super administrator can not be restored or permanently deleted', function () {
    $admin = User::factory()->admin()->make(['id' => 1]);
    $superAdmin = User::factory()->superAdmin()->make(['id' => 2, 'deleted_at' => now()]);

    expect($admin->can('forceDelete', $superAdmin))->toBeFalse();
});

test('the super administrator can not delete their own profile', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertForbidden();

    expect($superAdmin->fresh())->not->toBeNull();
});

test('only one super administrator can exist', function () {
    User::factory()->superAdmin()->create();

    expect(fn () => User::factory()->superAdmin()->create())->toThrow(QueryException::class);
});
