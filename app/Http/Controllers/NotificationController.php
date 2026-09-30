<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Mark one of the authenticated user's notifications as read and open it.
     */
    public function read(DatabaseNotification $notification): RedirectResponse
    {
        abort_unless(
            $notification->notifiable_type === Auth::user()->getMorphClass() && $notification->notifiable_id === Auth::id(),
            403
        );

        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('dashboard'));
    }

    /**
     * Mark all of the authenticated user's notifications as read.
     */
    public function readAll(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back();
    }
}
