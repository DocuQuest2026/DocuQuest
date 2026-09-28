<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('access', function () {
    test('guests are redirected to the login screen', function () {
        $account = User::factory()->staff()->create();

        $this->put(route('admin.accounts.password.update', $account), [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));
    });

    test('students and staff can not reset anyone\'s password', function () {
        $account = User::factory()->staff()->create();

        foreach ([User::factory()->create(), User::factory()->staff()->create()] as $actor) {
            $this->actingAs($actor)
                ->put(route('admin.accounts.password.update', $account), [
                    'password' => 'new-password',
                    'password_confirmation' => 'new-password',
                ])
                ->assertForbidden();
        }
    });
});

test('an administrator sets a new password for a staff account', function () {
    $admin = User::factory()->admin()->create();
    $account = User::factory()->staff()->create();

    $this->actingAs($admin)
        ->put(route('admin.accounts.password.update', $account), [
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(Hash::check('a-brand-new-password', $account->fresh()->password))->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'action' => 'user.password_reset',
        'subject_id' => $account->id,
    ]);
});

test('an administrator sets a new password for a student account', function () {
    $admin = User::factory()->admin()->create();
    $account = User::factory()->withStudentProfile()->create();

    $this->actingAs($admin)
        ->put(route('admin.accounts.password.update', $account), [
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])
        ->assertRedirect();

    expect(Hash::check('a-brand-new-password', $account->fresh()->password))->toBeTrue();
});

test('the new password must be confirmed', function () {
    $admin = User::factory()->admin()->create();
    $account = User::factory()->staff()->create();

    $this->actingAs($admin)
        ->put(route('admin.accounts.password.update', $account), [
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'does-not-match',
        ])
        ->assertSessionHasErrors('password', errorBag: 'resetPassword');
});
