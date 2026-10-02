<?php

namespace App\Providers;

use App\Enums\RequestStatus;
use App\Models\RecordRequest;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private const NAVIGATION_COUNTS_CACHE_KEY = 'navigation.request-counts';

    private const NAVIGATION_CACHE_SECONDS = 60;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->shareNavigationData();
    }

    /**
     * Load the navigation badges and notifications once per request. Every query is a round trip
     * to the remote database, so the results are cached briefly and forgotten as soon as the
     * underlying requests or notifications change.
     */
    protected function shareNavigationData(): void
    {
        foreach (['saved', 'deleted', 'restored', 'forceDeleted'] as $event) {
            RecordRequest::$event(fn () => Cache::forget(self::NAVIGATION_COUNTS_CACHE_KEY));
        }

        foreach (['saved', 'deleted'] as $event) {
            DatabaseNotification::$event(
                fn (DatabaseNotification $notification) => Cache::forget(self::navigationNotificationsCacheKey($notification->notifiable_id))
            );
        }

        View::composer('layouts.navigation', function ($view): void {
            $user = Auth::user();

            $statusCounts = Gate::forUser($user)->allows('viewAny', RecordRequest::class)
                ? Cache::remember(self::NAVIGATION_COUNTS_CACHE_KEY, self::NAVIGATION_CACHE_SECONDS, fn (): array => RecordRequest::query()
                    ->whereIn('status', [RequestStatus::CancellationRequested, RequestStatus::Pending])
                    ->selectRaw('status, count(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status')
                    ->all())
                : [];

            $notifications = $user instanceof User && $user->isOfficeUser()
                ? Cache::remember(self::navigationNotificationsCacheKey($user->getKey()), self::NAVIGATION_CACHE_SECONDS, fn (): array => [
                    'unread' => $user->unreadNotifications()->count(),
                    'recent' => $user->notifications()->latest()->limit(10)->get()
                        ->map(fn (DatabaseNotification $notification): array => [
                            'id' => $notification->id,
                            'read_at' => $notification->read_at?->toIso8601String(),
                            'data' => $notification->data,
                            'created_at' => $notification->created_at->toIso8601String(),
                        ])->all(),
                ])
                : ['unread' => 0, 'recent' => []];

            $view->with([
                'pendingCancellationsCount' => (int) ($statusCounts[RequestStatus::CancellationRequested->value] ?? 0),
                'pendingRequestsCount' => (int) ($statusCounts[RequestStatus::Pending->value] ?? 0),
                'unreadNotifications' => $notifications['unread'],
                'recentNotifications' => collect($notifications['recent'])->map(fn (array $notification): object => (object) [
                    ...$notification,
                    'created_at' => Carbon::parse($notification['created_at']),
                ]),
            ]);
        });
    }

    protected static function navigationNotificationsCacheKey(int|string|null $userId): string
    {
        return "navigation.notifications.{$userId}";
    }

    /**
     * Limit how often the public record request form can be submitted, both per
     * connection and per student number, so it can't be used to flood the registrar.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('record-requests', function (Request $request) {
            $onTooManyAttempts = function (Request $request, array $headers) {
                $minutes = max(1, (int) ceil(((int) ($headers['Retry-After'] ?? 60)) / 60));

                return back()
                    ->withInput()
                    ->withErrors(['throttle' => trans_choice(
                        'You have made too many requests. Please try again in :minutes minute.|You have made too many requests. Please try again in :minutes minutes.',
                        $minutes,
                        ['minutes' => $minutes],
                    )]);
            };

            $limits = [Limit::perMinutes(10, 5)->by('ip:'.$request->ip())->response($onTooManyAttempts)];

            if ($request->filled('student_no')) {
                $limits[] = Limit::perMinutes(10, 5)
                    ->by('student:'.mb_strtolower($request->string('student_no')->toString()))
                    ->response($onTooManyAttempts);
            }

            return $limits;
        });

        RateLimiter::for('record-request-cancellations', function (Request $request) {
            return Limit::perMinutes(10, 5)->by('ip:'.$request->ip())->response(function (Request $request, array $headers) {
                $minutes = max(1, (int) ceil(((int) ($headers['Retry-After'] ?? 60)) / 60));

                return back()->withInput()->withErrors(['throttle' => trans_choice(
                    'Too many attempts. Please try again in :minutes minute.|Too many attempts. Please try again in :minutes minutes.',
                    $minutes,
                    ['minutes' => $minutes],
                )]);
            });
        });

        $onHistoryThrottled = function (Request $request, array $headers) {
            $minutes = max(1, (int) ceil(((int) ($headers['Retry-After'] ?? 60)) / 60));

            return back()->withInput()->withErrors(['throttle' => trans_choice(
                'Too many attempts. Please try again in :minutes minute.|Too many attempts. Please try again in :minutes minutes.',
                $minutes,
                ['minutes' => $minutes],
            )]);
        };

        // Sending a code is limited per connection and per email, so one inbox can't be flooded.
        RateLimiter::for('record-request-history', function (Request $request) use ($onHistoryThrottled) {
            return [
                Limit::perMinutes(10, 5)->by('ip:'.$request->ip())->response($onHistoryThrottled),
                Limit::perMinutes(10, 3)->by('email:'.mb_strtolower($request->string('email')->toString()))->response($onHistoryThrottled),
            ];
        });

        RateLimiter::for('record-request-history-verify', function (Request $request) use ($onHistoryThrottled) {
            return Limit::perMinutes(10, 10)->by('ip:'.$request->ip())->response($onHistoryThrottled);
        });

        // Reference numbers are high-entropy and hard to guess, but this still limits how
        // fast someone could script through attempts.
        RateLimiter::for('record-request-status', function (Request $request) {
            return Limit::perMinutes(10, 5)->by('ip:'.$request->ip())->response(function (Request $request, array $headers) {
                $minutes = max(1, (int) ceil(((int) ($headers['Retry-After'] ?? 60)) / 60));

                return back()->withInput()->withErrors(['throttle' => trans_choice(
                    'Too many attempts. Please try again in :minutes minute.|Too many attempts. Please try again in :minutes minutes.',
                    $minutes,
                    ['minutes' => $minutes],
                )]);
            });
        });
    }
}
