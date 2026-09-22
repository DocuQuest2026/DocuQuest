<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffAccountRequest;
use App\Http\Requests\Admin\UpdateStaffAccountRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffAccountController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * List the registrar's office accounts.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.staff.index', [
            'accounts' => User::office()->orderBy('name')->orderBy('id')->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a new office account.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.staff.create', ['roles' => Role::officeRoles()]);
    }

    /**
     * Create an office account. The administrator never sees a password: the new user
     * receives a link to set their own.
     */
    public function store(StoreStaffAccountRequest $request): RedirectResponse
    {
        $account = new User($request->safe()->only(['name', 'email']));
        $account->password = Str::random(40);
        $account->role = Role::from($request->validated('role'));
        $account->email_verified_at = now();
        $account->save();

        $this->audit->log($request->user(), 'staff.created', $account, ['role' => $account->role->value]);

        Password::sendResetLink(['email' => $account->email]);

        return redirect()
            ->route('admin.staff.index')
            ->with('status', __('Account created. :name was emailed a link to set their password.', ['name' => $account->name]));
    }

    /**
     * Show the form for editing an office account.
     */
    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.staff.edit', ['account' => $user, 'roles' => Role::officeRoles()]);
    }

    /**
     * Update an office account.
     */
    public function update(UpdateStaffAccountRequest $request, User $user): RedirectResponse
    {
        $wasActive = $user->is_active;
        $previousRole = $user->role;

        $user->fill($request->safe()->only(['name', 'email']));
        $user->role = Role::from($request->validated('role'));
        $user->is_active = $request->boolean('is_active');
        $user->save();

        $changedFields = array_keys(Arr::except($user->getChanges(), 'updated_at'));

        if ($changedFields !== []) {
            $this->audit->log($request->user(), 'staff.updated', $user, ['changes' => $changedFields]);
        }

        if ($previousRole !== $user->role) {
            $this->audit->log($request->user(), 'staff.role_changed', $user, [
                'from' => $previousRole->value,
                'to' => $user->role->value,
            ]);
        }

        if ($wasActive !== $user->is_active) {
            $this->audit->log($request->user(), $user->is_active ? 'user.reactivated' : 'user.deactivated', $user);
        }

        return redirect()->route('admin.staff.index')->with('status', __('Account updated.'));
    }
}
