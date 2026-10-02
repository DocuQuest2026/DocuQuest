<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendRecordRequestHistoryCodeRequest;
use App\Http\Requests\VerifyRecordRequestHistoryCodeRequest;
use App\Mail\RecordRequestHistoryCode;
use App\Models\RecordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class RecordRequestHistoryController extends Controller
{
    private const CODE_MINUTES = 10;

    private const ACCESS_MINUTES = 15;

    private const MAX_CODE_ATTEMPTS = 5;

    /**
     * Show the email form, the code form once a code was sent, or the verified requester's
     * request history.
     */
    public function create(): View
    {
        $verifiedEmail = $this->verifiedEmail();

        return view('record-requests.history', [
            'pendingEmail' => session('history_pending_email'),
            'verifiedEmail' => $verifiedEmail,
            'recordRequests' => $verifiedEmail
                ? RecordRequest::where('email', $verifiedEmail)->with('release')->latest()->get()
                : null,
        ]);
    }

    /**
     * Email a one-time code to the address, but only when it has requests. The response is the
     * same either way, so the form can not be used to find out which emails are on file.
     */
    public function store(SendRecordRequestHistoryCodeRequest $request): RedirectResponse
    {
        $email = $request->validated('email');

        if (RecordRequest::where('email', $email)->exists()) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = now()->addMinutes(self::CODE_MINUTES);

            Cache::put($this->codeCacheKey($email), [
                'hash' => $this->hashCode($code),
                'attempts' => 0,
                'expires_at' => $expiresAt->getTimestamp(),
            ], $expiresAt);

            try {
                Mail::to($email)->send(new RecordRequestHistoryCode($code, self::CODE_MINUTES));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        session()->forget('history_access');
        session()->put('history_pending_email', $email);

        return redirect()->route('record-requests.history.create');
    }

    /**
     * Check the emailed code and, if it matches, unlock the history for a short time.
     */
    public function verify(VerifyRecordRequestHistoryCodeRequest $request): RedirectResponse
    {
        $email = session('history_pending_email');

        if (! $email) {
            return redirect()->route('record-requests.history.create');
        }

        $key = $this->codeCacheKey($email);
        $pending = Cache::get($key);

        if (! $pending || $pending['attempts'] >= self::MAX_CODE_ATTEMPTS) {
            Cache::forget($key);

            return back()->withErrors(['code' => __('That code has expired. Please request a new one.')]);
        }

        if (! hash_equals($pending['hash'], $this->hashCode($request->validated('code')))) {
            Cache::put($key, [...$pending, 'attempts' => $pending['attempts'] + 1], Carbon::createFromTimestamp($pending['expires_at']));

            return back()->withErrors(['code' => __('That code is not correct.')]);
        }

        Cache::forget($key);
        session()->forget('history_pending_email');
        session()->put('history_access', [
            'email' => $email,
            'expires_at' => now()->addMinutes(self::ACCESS_MINUTES)->getTimestamp(),
        ]);

        return redirect()->route('record-requests.history.create');
    }

    /**
     * Forget the email and any unlocked history so a different email can be used.
     */
    public function clear(): RedirectResponse
    {
        session()->forget(['history_pending_email', 'history_access']);

        return redirect()->route('record-requests.history.create');
    }

    /**
     * The email whose history was unlocked with a code and has not timed out yet.
     */
    private function verifiedEmail(): ?string
    {
        $access = session('history_access');

        if (! $access || $access['expires_at'] < now()->getTimestamp()) {
            session()->forget('history_access');

            return null;
        }

        return $access['email'];
    }

    private function codeCacheKey(string $email): string
    {
        return 'record-request-history-code:'.sha1($email);
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
