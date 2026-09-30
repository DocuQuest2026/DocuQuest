<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffAccountRequest;
use App\Http\Requests\Admin\UpdateStaffAccountRequest;
use App\Mail\StaffAccountCredentials;
use App\Models\AuditLog;
use App\Models\DocumentRelease;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class StaffAccountController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * List the registrar's office accounts, or, on the "Deleted" view, only deleted ones.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $showingDeleted = $request->boolean('deleted');

        $accounts = User::office()
            ->when($showingDeleted, fn ($query) => $query->onlyTrashed())
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.staff.index', [
            'accounts' => $accounts,
            'showingDeleted' => $showingDeleted,
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
     * Create an office account with the password the administrator set. The new user is
     * emailed their sign-in details.
     */
    public function store(StoreStaffAccountRequest $request): RedirectResponse
    {
        $password = $request->validated('password');

        $account = new User($request->safe()->only(['name', 'email']));
        $account->password = $password;
        $account->role = Role::from($request->validated('role'));
        $account->email_verified_at = now();
        $account->save();

        $this->audit->log($request->user(), 'staff.created', $account, ['role' => $account->role->value]);

        try {
            Mail::to($account->email)->send(new StaffAccountCredentials($account, $password));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('admin.staff.index')
            ->with('status', __(':name was emailed their sign-in details.', ['name' => $account->name]));
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

    /**
     * Soft delete an office account. The account and its audit trail are kept, and it can no
     * longer sign in.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $this->audit->log($request->user(), 'staff.deleted', $user);
        $user->delete();

        return redirect()->route('admin.staff.index')->with('status', __('Account deleted.'));
    }

    /**
     * Recover a previously deleted office account.
     */
    public function restore(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('restore', $user);

        $user->restore();
        $this->audit->log($request->user(), 'staff.restored', $user);

        return redirect()->route('admin.staff.index')->with('status', __('Account restored.'));
    }

    /**
     * Permanently erase a deleted office account. Refused when audit or release history
     * still references it, since that history can never be attributed to anyone again.
     */
    public function forceDelete(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('forceDelete', $user);

        $hasHistory = AuditLog::where('actor_id', $user->id)->exists()
            || DocumentRelease::where('released_by', $user->id)->exists();

        if ($hasHistory) {
            return redirect()
                ->route('admin.staff.index', ['deleted' => 1])
                ->with('error', __(':name can not be permanently deleted: their audit or release history is still on record.', ['name' => $user->name]));
        }

        $this->audit->log($request->user(), 'staff.permanently_deleted', $user, [
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $user->forceDelete();

        return redirect()->route('admin.staff.index', ['deleted' => 1])->with('status', __('Account permanently deleted.'));
    }
}
