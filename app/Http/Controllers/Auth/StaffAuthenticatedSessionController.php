<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StaffLoginRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffAuthenticatedSessionController extends Controller
{
    /**
     * Display the staff and administrator login view.
     */
    public function create(): View
    {
        return view('auth.staff-login');
    }

    /**
     * Handle an incoming staff or administrator authentication request.
     */
    public function store(StaffLoginRequest $request, AuditLogger $audit): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $request->user()->forceFill(['last_seen_at' => now()])->saveQuietly();

        $audit->log($request->user(), 'auth.login');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()?->forceFill(['last_seen_at' => null])->saveQuietly();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
