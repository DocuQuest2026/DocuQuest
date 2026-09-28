<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAccountPasswordRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;

class AccountPasswordController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Set a new password for the given account.
     */
    public function update(UpdateAccountPasswordRequest $request, User $user): RedirectResponse
    {
        $user->password = $request->validated('password');
        $user->save();

        $this->audit->log($request->user(), 'user.password_reset', $user);

        return back()->with('status', __(':name\'s password has been changed.', ['name' => $user->name]));
    }
}
