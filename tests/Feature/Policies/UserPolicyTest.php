<?php

use App\Enums\Role;
use App\Models\User;

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
