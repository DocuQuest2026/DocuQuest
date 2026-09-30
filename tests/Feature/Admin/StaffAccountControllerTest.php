<?php

use App\Enums\Role;
use App\Mail\StaffAccountCredentials;
use App\Models\AuditLog;
use App\Models\DocumentRelease;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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
    /**
     * @return array<string, mixed>
     */
    function validStaffAccount(array $overrides = []): array
    {
        return [
            'name' => 'Ana Staff',
            'email' => 'ana@example.com',
            'role' => 'staff',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
            ...$overrides,
        ];
    }

    test('students and staff can not create accounts', function (Role $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->post(route('admin.staff.store'), validStaffAccount(['email' => 'x@example.com']))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    })->with([Role::Student, Role::Staff]);

    test('an administrator creates a verified staff account and emails their sign-in details', function () {
        Mail::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.staff.store'), validStaffAccount())
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('status');

        $account = User::where('email', 'ana@example.com')->firstOrFail();

        expect($account->role)->toBe(Role::Staff)
            ->and($account->is_active)->toBeTrue()
            ->and($account->hasVerifiedEmail())->toBeTrue()
            ->and(Hash::check('a-strong-password', $account->password))->toBeTrue();

        Mail::assertSent(StaffAccountCredentials::class, function (StaffAccountCredentials $mail) use ($account) {
            return $mail->hasTo($account->email)
                && $mail->account->is($account)
                && $mail->password === 'a-strong-password'
                && str_contains($mail->render(), 'a-strong-password');
        });

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'staff.created',
            'subject_id' => $account->id,
        ]);
    });

    test('the password set during creation can be used to sign in', function () {
        Mail::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.staff.store'), validStaffAccount());

        $account = User::where('email', 'ana@example.com')->firstOrFail();

        // Log the admin out first: the staff login route is guest-only, and actingAs() would
        // otherwise leave the admin authenticated for the rest of this test.
        $this->post('/logout');

        $this->post(config('auth.staff_login_path'), ['email' => 'ana@example.com', 'password' => 'a-strong-password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($account);
    });

    test('an administrator can create another administrator', function () {
        Mail::fake();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), validStaffAccount(['name' => 'Bea Admin', 'email' => 'bea@example.com', 'role' => 'admin']));

        expect(User::where('email', 'bea@example.com')->firstOrFail()->role)->toBe(Role::Admin);
    });

    test('an office account can not be created with the student role', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), validStaffAccount(['name' => 'Sam', 'email' => 'sam@example.com', 'role' => 'student']))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'sam@example.com']);
    });

    test('an account can not reuse an existing email address', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), validStaffAccount(['name' => 'Dup', 'email' => 'taken@example.com']))
            ->assertSessionHasErrors('email');
    });

    test('name, email, role and password are required', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'role', 'password']);
    });

    test('the password confirmation must match', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), validStaffAccount(['password_confirmation' => 'does-not-match']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
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

describe('destroy', function () {
    test('the edit page shows a delete button for another office account', function () {
        $account = User::factory()->staff()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.staff.edit', $account))
            ->assertOk()
            ->assertSee(__('Delete account'));
    });

    test('the edit page does not show a delete button for your own account', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.staff.edit', $admin))
            ->assertOk()
            ->assertDontSee(__('Delete account'));
    });

    test('an administrator deletes another office account', function () {
        $admin = User::factory()->admin()->create();
        $account = User::factory()->staff()->create();

        $this->actingAs($admin)
            ->delete(route('admin.staff.destroy', $account))
            ->assertRedirect(route('admin.staff.index'));

        expect(User::find($account->id))->toBeNull()
            ->and(User::withTrashed()->find($account->id))->not->toBeNull();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'staff.deleted',
            'subject_id' => $account->id,
        ]);
    });

    test('a deleted account no longer appears in the list', function () {
        $admin = User::factory()->admin()->create();
        $account = User::factory()->staff()->create();

        $this->actingAs($admin)->delete(route('admin.staff.destroy', $account));

        $this->actingAs($admin)->get(route('admin.staff.index'))->assertDontSee($account->email);
    });

    test('a deleted account can not sign in', function () {
        $account = User::factory()->staff()->create(['email' => 'deleted.staff@docuquest.test']);
        $account->delete();

        $this->post(config('auth.staff_login_path'), ['email' => 'deleted.staff@docuquest.test', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);
        $this->assertGuest();
    });

    test('an administrator can not delete their own account', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->delete(route('admin.staff.destroy', $admin))->assertForbidden();

        expect(User::find($admin->id))->not->toBeNull();
    });

    test('staff can not delete accounts', function () {
        $target = User::factory()->staff()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.staff.destroy', $target))
            ->assertForbidden();

        expect(User::find($target->id))->not->toBeNull();
    });

    test('a student account can not be deleted through the staff screens', function () {
        $student = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.staff.destroy', $student))
            ->assertForbidden();
    });

    test('guests are redirected to log in when deleting an account', function () {
        $account = User::factory()->staff()->create();

        $this->delete(route('admin.staff.destroy', $account))->assertRedirect(route('login'));
    });
});

describe('restore', function () {
    test('an administrator restores a deleted account', function () {
        $admin = User::factory()->admin()->create();
        $account = User::factory()->staff()->create();
        $account->delete();

        $this->actingAs($admin)
            ->post(route('admin.staff.restore', $account))
            ->assertRedirect(route('admin.staff.index'));

        expect(User::find($account->id))->not->toBeNull();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'staff.restored',
            'subject_id' => $account->id,
        ]);
    });

    test('the deleted accounts view shows only deleted accounts', function () {
        $deleted = User::factory()->staff()->create(['name' => 'Deleted Staff']);
        $deleted->delete();
        $active = User::factory()->staff()->create(['name' => 'Active Staff']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.staff.index', ['deleted' => 1]))
            ->assertOk()
            ->assertSee('Deleted Staff')
            ->assertDontSee('Active Staff');
    });

    test('an active account can not be restored', function () {
        $admin = User::factory()->admin()->create();
        $account = User::factory()->staff()->create();

        $this->actingAs($admin)->post(route('admin.staff.restore', $account))->assertForbidden();
    });

    test('staff can not restore accounts', function () {
        $account = User::factory()->staff()->create();
        $account->delete();

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.staff.restore', $account))
            ->assertForbidden();
    });

    test('guests are redirected to log in when restoring an account', function () {
        $account = User::factory()->staff()->create();
        $account->delete();

        $this->post(route('admin.staff.restore', $account))->assertRedirect(route('login'));
    });
});

describe('force delete', function () {
    test('an administrator permanently deletes a deleted account with no history', function () {
        $admin = User::factory()->admin()->create();
        $account = User::factory()->staff()->create();
        $account->delete();

        $this->actingAs($admin)
            ->delete(route('admin.staff.force-delete', $account))
            ->assertRedirect(route('admin.staff.index', ['deleted' => 1]))
            ->assertSessionHas('status');

        expect(User::withTrashed()->find($account->id))->toBeNull();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'staff.permanently_deleted',
            'subject_id' => $account->id,
        ]);
    });

    test('an account with audit log history can not be permanently deleted', function () {
        $admin = User::factory()->admin()->create();
        $account = User::factory()->staff()->create();
        $account->delete();
        AuditLog::factory()->create(['actor_id' => $account->id]);

        $this->actingAs($admin)
            ->delete(route('admin.staff.force-delete', $account))
            ->assertRedirect(route('admin.staff.index', ['deleted' => 1]))
            ->assertSessionHas('error');

        expect(User::withTrashed()->find($account->id))->not->toBeNull();
    });

    test('an account with document release history can not be permanently deleted', function () {
        $admin = User::factory()->admin()->create();
        $account = User::factory()->staff()->create();
        $account->delete();
        DocumentRelease::factory()->create(['released_by' => $account->id]);

        $this->actingAs($admin)
            ->delete(route('admin.staff.force-delete', $account))
            ->assertRedirect(route('admin.staff.index', ['deleted' => 1]))
            ->assertSessionHas('error');

        expect(User::withTrashed()->find($account->id))->not->toBeNull();
    });

    test('an active account can not be permanently deleted', function () {
        $admin = User::factory()->admin()->create();
        $account = User::factory()->staff()->create();

        $this->actingAs($admin)->delete(route('admin.staff.force-delete', $account))->assertForbidden();
    });

    test('staff can not permanently delete accounts', function () {
        $account = User::factory()->staff()->create();
        $account->delete();

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.staff.force-delete', $account))
            ->assertForbidden();
    });

    test('guests are redirected to log in when permanently deleting an account', function () {
        $account = User::factory()->staff()->create();
        $account->delete();

        $this->delete(route('admin.staff.force-delete', $account))->assertRedirect(route('login'));
    });
});
