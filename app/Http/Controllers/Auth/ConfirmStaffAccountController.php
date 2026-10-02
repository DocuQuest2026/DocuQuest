<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Mail\StaffAccountCredentials;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class ConfirmStaffAccountController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Create the office account the administrator set up, but only now that the owner of the
     * email address has opened the signed link sent to it. An address that does not exist
     * never receives the link, so no account is created for it.
     */
    public function __invoke(Request $request): View
    {
        $details = $this->details((string) $request->query('payload'));

        if ($details === null) {
            return view('auth.staff-account-confirmed', ['state' => 'invalid', 'email' => null]);
        }

        if (User::withTrashed()->where('email', $details['email'])->exists()) {
            return view('auth.staff-account-confirmed', ['state' => 'exists', 'email' => $details['email']]);
        }

        $account = new User(['name' => $details['name'], 'email' => $details['email']]);
        $account->password = $details['password'];
        $account->role = Role::from($details['role']);
        $account->email_verified_at = now();
        $account->save();

        $this->audit->log(User::find($details['invited_by']), 'staff.created', $account, ['role' => $account->role->value]);

        try {
            Mail::to($account->email)->send(new StaffAccountCredentials($account, $details['password']));
        } catch (Throwable $exception) {
            report($exception);
        }

        return view('auth.staff-account-confirmed', ['state' => 'created', 'email' => $account->email]);
    }

    /**
     * The account details carried by the link, or null if the link was tampered with or was
     * set up by someone who is no longer an active administrator.
     *
     * @return array{name: string, email: string, role: string, password: string, invited_by: int}|null
     */
    private function details(string $payload): ?array
    {
        try {
            $details = json_decode(Crypt::decryptString($payload), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            return null;
        }

        if (! is_array($details)
            || ! isset($details['name'], $details['email'], $details['role'], $details['password'], $details['invited_by'])
            || ! in_array(Role::tryFrom((string) $details['role']), Role::officeRoles(), true)) {
            return null;
        }

        $inviter = User::find($details['invited_by']);

        return $inviter?->isAdmin() && $inviter->is_active ? $details : null;
    }
}
