<?php

use App\Enums\Role;
use App\Models\RecordRequest;
use App\Models\User;

test('only staff and admins may approve, reject, or release a pending or approved request', function (Role $role, bool $allowed) {
    $user = User::factory()->make(['role' => $role]);
    $pending = RecordRequest::factory()->make();
    $approved = RecordRequest::factory()->approved()->make();

    expect($user->can('approve', $pending))->toBe($allowed)
        ->and($user->can('reject', $pending))->toBe($allowed)
        ->and($user->can('release', $approved))->toBe($allowed);
})->with([
    'student' => [Role::Student, false],
    'staff' => [Role::Staff, true],
    'admin' => [Role::Admin, true],
]);

test('approve and reject are only allowed while pending', function (string $state) {
    $staff = User::factory()->staff()->make();
    $recordRequest = $state === 'pending' ? RecordRequest::factory()->make() : RecordRequest::factory()->{$state}()->make();

    expect($staff->can('approve', $recordRequest))->toBe($state === 'pending')
        ->and($staff->can('reject', $recordRequest))->toBe($state === 'pending');
})->with(['pending', 'approved', 'released', 'rejected', 'cancellationRequested', 'cancelled']);

test('release is only allowed once approved', function (string $state) {
    $staff = User::factory()->staff()->make();
    $recordRequest = $state === 'pending' ? RecordRequest::factory()->make() : RecordRequest::factory()->{$state}()->make();

    expect($staff->can('release', $recordRequest))->toBe($state === 'approved');
})->with(['pending', 'approved', 'released', 'rejected', 'cancellationRequested', 'cancelled']);

test('confirming or denying a cancellation is only allowed while requested', function (string $state) {
    $staff = User::factory()->staff()->make();
    $recordRequest = $state === 'pending' ? RecordRequest::factory()->make() : RecordRequest::factory()->{$state}()->make();

    expect($staff->can('confirmCancellation', $recordRequest))->toBe($state === 'cancellationRequested')
        ->and($staff->can('denyCancellation', $recordRequest))->toBe($state === 'cancellationRequested');
})->with(['pending', 'approved', 'released', 'rejected', 'cancellationRequested', 'cancelled']);

test('only staff and admins may confirm or deny a requested cancellation', function (Role $role, bool $allowed) {
    $user = User::factory()->make(['role' => $role]);
    $recordRequest = RecordRequest::factory()->cancellationRequested()->make();

    expect($user->can('confirmCancellation', $recordRequest))->toBe($allowed)
        ->and($user->can('denyCancellation', $recordRequest))->toBe($allowed);
})->with([
    'student' => [Role::Student, false],
    'staff' => [Role::Staff, true],
    'admin' => [Role::Admin, true],
]);
