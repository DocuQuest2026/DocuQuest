<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastSeen
{
    /**
     * How often, at most, a request is allowed to update the timestamp. Keeps active users
     * looking online without writing to the database on every single request.
     */
    private const UPDATE_EVERY_MINUTES = 1;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && (
            $user->last_seen_at === null
            || $user->last_seen_at->isBefore(now()->subMinutes(self::UPDATE_EVERY_MINUTES))
        )) {
            $user->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
