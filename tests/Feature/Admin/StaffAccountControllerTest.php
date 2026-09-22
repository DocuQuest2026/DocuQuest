<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

describe('access', function () {
    test('guests are redirected to the login screen', function () {
        $this->get(route('admin.staff.index'))->assertRedirect(route('login'));
    });

    test('students are forbidden', function () {
        $this->actingAs(User::factory()->create())->get(route('admin.staff.index'))->assertForbidden();
    });

    test('registrar staff are forbidden', function () {
        $this->actingAs(User::factory()->staff()->create())->get(route('admin.staff.index'))->assertForbidden();
    });

    test('unverified administrators are sent to verify their email', function () {
        $this->actingAs(User::factory()->admin()->unverified()->create())
            ->get(route('admin.staff.index'))
            ->assertRedirect(route('verification.notice'));
    });
});

describe('index', function () {
    test('administrators see office accounts but not students', function () {
        $staff = User::factory()->staff()->create(['name' => 'Ana Staff']);
        $student = User::factory()->create(['name' => 'Sam Student']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee($staff->name)
            ->assertDontSee($student->name);
    });
});

describe('store', function () {
    test('students and staff can not create accounts', function (Role $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->post(route('admin.staff.store'), ['name' => 'X', 'email' => 'x@example.com', 'role' => 'staff'])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    })->with([Role::Student, Role::Staff]);

    test('an administrator creates a verified staff account and emails a password setup link', function () {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.staff.store'), ['name' => 'Ana Staff', 'email' => 'ana@example.com', 'role' => 'staff'])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('status');

        $account = User::where('email', 'ana@example.com')->firstOrFail();

        expect($account->role)->toBe(Role::Staff)
            ->and($account->is_active)->toBeTrue()
            ->and($account->hasVerifiedEmail())->toBeTrue();

        Notification::assertSentTo($account, ResetPassword::class);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'staff.created',
            'subject_id' => $account->id,
        ]);
    });

    test('an administrator can create another administrator', function () {
        Notification::fake();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), ['name' => 'Bea Admin', 'email' => 'bea@example.com', 'role' => 'admin']);

        expect(User::where('email', 'bea@example.com')->firstOrFail()->role)->toBe(Role::Admin);
    });

    test('an office account can not be created with the student role', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), ['name' => 'Sam', 'email' => 'sam@example.com', 'role' => 'student'])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'sam@example.com']);
    });

    test('an account can not reuse an existing email address', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), ['name' => 'Dup', 'email' => 'taken@example.com', 'role' => 'staff'])
            ->assertSessionHasErrors('email');
    });

    test('name, email and role are required', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'role']);
    });
});

describe('update', function () {
    test('staff can not update accounts', function () {
        $target = User::factory()->staff()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.staff.update', $target), ['name' => 'Hacked', 'email' => $target->email, 'role' => 'admin', 'is_active' => 1])
            ->assertForbidden();

        expect($target->fresh()->role)->toBe(Role::Staff);
    });

    test('a student account can not be edited through the staff screens', function () {
        $student = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.staff.edit', $student))
            ->assertForbidden();
    });

    test('an administrator updates a staff account and the change is audited', function () {
        $admin = User::factory()->admin()->create();
        $account = User::factory()->staff()->create();

        $this->actingAs($admin)
            ->put(route('admin.staff.update', $account), [
                'name' => 'Renamed Staff',
                'email' => $account->email,
                'role' => 'admin',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.staff.index'));

        expect($account->fresh())->name->toBe('Renamed Staff')->role->toBe(Role::Admin);

        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'staff.updated', 'subject_id' => $account->id]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'staff.role_changed', 'subject_id' => $account->id]);
    });

    test('deactivating and reactivating an account is audited', function () {
        $admin = User::factory()->admin()->create();
        $account = User::factory()->staff()->create();
        $payload = ['name' => $account->name, 'email' => $account->email, 'role' => 'staff'];

        $this->actingAs($admin)->put(route('admin.staff.update', $account), [...$payload, 'is_active' => 0]);

        expect($account->fresh()->is_active)->toBeFalse();
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deactivated', 'subject_id' => $account->id]);

        $this->actingAs($admin)->put(route('admin.staff.update', $account), [...$payload, 'is_active' => 1]);

        expect($account->fresh()->is_active)->toBeTrue();
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.reactivated', 'subject_id' => $account->id]);
    });

    test('an unchanged save does not write an update audit entry', function () {
        $account = User::factory()->staff()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.staff.update', $account), [
                'name' => $account->name,
                'email' => $account->email,
                'role' => 'staff',
                'is_active' => 1,
            ]);

        $this->assertDatabaseMissing('audit_logs', ['action' => 'staff.updated']);
    });

    test('an administrator can not deactivate or demote themselves', function () {
        $admin = User::factory()->admin()->create();
        $payload = ['name' => $admin->name, 'email' => $admin->email];

        $this->actingAs($admin)
            ->put(route('admin.staff.update', $admin), [...$payload, 'role' => 'admin', 'is_active' => 0])
            ->assertSessionHasErrors(['is_active' => 'You cannot deactivate your own account.']);

        $this->actingAs($admin)
            ->put(route('admin.staff.update', $admin), [...$payload, 'role' => 'staff', 'is_active' => 1])
            ->assertSessionHasErrors(['role' => 'You cannot change your own role.']);

        expect($admin->fresh())->is_active->toBeTrue()->role->toBe(Role::Admin);
    });

    test('an administrator can keep their own email while saving', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.staff.update', $admin), ['name' => 'New Name', 'email' => $admin->email, 'role' => 'admin', 'is_active' => 1])
            ->assertSessionHasNoErrors();

        expect($admin->fresh()->name)->toBe('New Name');
    });
});
