<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyEmailRequest extends EmailVerificationRequest
{
    /**
     * Redirect back to the dashboard with a friendly message instead of a bare 403 when this
     * link belongs to a different account than the one currently signed in. This happens when
     * someone is redirected here by Laravel's post-login "intended URL", after having clicked
     * a verification link meant for a different account while signed out or signed in as
     * someone else (e.g. an admin who clicked the link for a staff account they created).
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            redirect()->route('dashboard')->with('status', __(
                'That verification link is not for your account. Please sign in as the account it was sent to.'
            ))
        );
    }
}
