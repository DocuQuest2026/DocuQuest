<?php

use App\Enums\Role;
use App\Mail\ConfirmStaffAccountEmail;
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

    test('an unverified account is flagged so it is not mistaken for a working one', function () {
        $unverified = User::factory()->staff()->unverified()->create(['name' => 'Fake Staff']);
        $verified = User::factory()->staff()->create(['name' => 'Real Staff']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee(__('Unverified'))
            ->assertSee('Fake Staff')
            ->assertSee('Real Staff');

        $response->assertSeeInOrder(['Fake Staff', __('Unverified')]);
    });

    test('the list shows online, offline, and deactivated status correctly', function () {
        $online = User::factory()->staff()->create(['name' => 'Online Staff', 'last_seen_at' => now()]);
        $offline = User::factory()->staff()->create(['name' => 'Offline Staff', 'last_seen_at' => now()->subHours(2)]);
        $neverSeen = User::factory()->staff()->create(['name' => 'Never Seen Staff', 'last_seen_at' => null]);
        $deactivated = User::factory()->staff()->inactive()->create(['name' => 'Deactivated Staff', 'last_seen_at' => now()]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.staff.index'))
            ->assertOk();

        $response->assertSeeInOrder([$online->name, __('Online')]);
        $response->assertSeeInOrder([$offline->name, __('Offline')]);
        $response->assertSeeInOrder([$neverSeen->name, __('Offline')]);
        $response->assertSeeInOrder([$deactivated->name, __('Deactivated')]);
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
            'email' => 'ana.staff@gmail.com',
            'role' => 'staff',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
            ...$overrides,
        ];
    }

    test('students and staff can not create accounts', function (Role $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->post(route('admin.staff.store'), validStaffAccount(['email' => 'xtestuser@gmail.com']))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'xtestuser@gmail.com']);
    })->with([Role::Student, Role::Staff]);

    /**
     * Submit the admin form and return the confirmation link that was emailed to the new user.
     */
    function sendConfirmationLink(array $overrides = [], ?User $admin = null): string
    {
        Mail::fake();

        test()->actingAs($admin ?? User::factory()->admin()->create())
            ->post(route('admin.staff.store'), validStaffAccount($overrides))
            ->assertRedirect(route('admin.staff.index'));

        $url = null;

        Mail::assertSent(ConfirmStaffAccountEmail::class, function (ConfirmStaffAccountEmail $mail) use (&$url): bool {
            $url = $mail->confirmationUrl;

            return true;
        });

        return $url;
    }

    test('submitting the form only emails a confirmation link and creates no account yet', function () {
        Mail::fake();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), validStaffAccount())
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('status');

        Mail::assertSent(ConfirmStaffAccountEmail::class, fn (ConfirmStaffAccountEmail $mail): bool => $mail->hasTo('ana.staff@gmail.com'));
        Mail::assertNotSent(StaffAccountCredentials::class);
        $this->assertDatabaseMissing('users', ['email' => 'ana.staff@gmail.com']);
    });

    test('opening the confirmation link creates a verified account and emails the sign-in details', function () {
        $admin = User::factory()->admin()->create();
        $url = sendConfirmationLink(admin: $admin);

        Mail::fake();
        auth()->logout();

        $this->get($url)->assertOk()->assertSee('Your account has been created')->assertSee("An administrator created a registrar's office account for you");

        $account = User::where('email', 'ana.staff@gmail.com')->firstOrFail();

        expect($account->role)->toBe(Role::Staff)
            ->and($account->is_active)->toBeTrue()
            ->and($account->hasVerifiedEmail())->toBeTrue()
            ->and(Hash::check('a-strong-password', $account->password))->toBeTrue();

        Mail::assertSent(StaffAccountCredentials::class, fn (StaffAccountCredentials $mail): bool => $mail->hasTo($account->email) && $mail->password === 'a-strong-password');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'staff.created',
            'subject_id' => $account->id,
        ]);
    });

    test('the new staff member can sign in right after confirming, without another verification step', function () {
        $url = sendConfirmationLink();

        $this->post('/logout');
        $this->get($url);

        $account = User::where('email', 'ana.staff@gmail.com')->firstOrFail();

        $this->post(config('auth.staff_login_path'), ['email' => 'ana.staff@gmail.com', 'password' => 'a-strong-password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($account);

        $this->get(route('dashboard'))->assertOk();
    });

    test('an administrator can create another administrator through the link', function () {
        $url = sendConfirmationLink(['name' => 'Bea Admin', 'email' => 'bea.admin@gmail.com', 'role' => 'admin']);

        $this->post('/logout');
        $this->get($url);

        expect(User::where('email', 'bea.admin@gmail.com')->firstOrFail()->role)->toBe(Role::Admin);
    });

    test('a tampered, unsigned or expired link creates nothing', function () {
        $url = sendConfirmationLink();

        $this->post('/logout');

        $this->get(route('staff-accounts.confirm', ['payload' => 'garbage']))->assertForbidden();
        $this->get(str_replace('payload=', 'payload=x', $url))->assertForbidden();

        $this->travel(25)->hours();
        $this->get($url)->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'ana.staff@gmail.com']);
    });

    test('opening the link twice does not create a second account', function () {
        $url = sendConfirmationLink();

        $this->post('/logout');
        $this->get($url);
        $this->get($url)->assertOk()->assertSee('Your account is already created');

        expect(User::where('email', 'ana.staff@gmail.com')->count())->toBe(1);
    });

    test('a link set up by someone who is no longer an administrator creates nothing', function () {
        $admin = User::factory()->admin()->create();
        $url = sendConfirmationLink(admin: $admin);

        $admin->forceFill(['role' => Role::Staff])->save();
        auth()->logout();

        $this->get($url)->assertOk()->assertSee('This link is not valid');

        $this->assertDatabaseMissing('users', ['email' => 'ana.staff@gmail.com']);
    });

    test('nothing is created and an error is shown if the confirmation email can not be sent', function () {
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP down'));

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), validStaffAccount())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'ana.staff@gmail.com']);
    });

    test('an office account can not be created with the student role', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), validStaffAccount(['name' => 'Sam', 'email' => 'sam.student@gmail.com', 'role' => 'student']))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'sam.student@gmail.com']);
    });

    test('an account can not reuse an existing email address', function () {
        User::factory()->create(['email' => 'taken.staff@gmail.com']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), validStaffAccount(['name' => 'Dup', 'email' => 'taken.staff@gmail.com']))
            ->assertSessionHasErrors('email');
    });

    test('name, email, role and password are required', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'role', 'password']);
    });

    test('the email must be a real gmail address', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), validStaffAccount(['email' => 'ana.staff@yahoo.com']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'ana.staff@yahoo.com']);
    });

    test('the password confirmation must match', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staff.store'), validStaffAccount(['password_confirmation' => 'does-not-match']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'ana.staff@gmail.com']);
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
