<?php

namespace App\Providers;

use App\Enums\RequestStatus;
use App\Models\DocumentRelease;
use App\Models\RecordRequest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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

        // The automated browser tests must use the built assets, not whichever Vite dev server
        // happens to be running on the machine.
        if ($this->app->environment(['e2e', 'dusk.local'])) {
            Vite::useHotFile(storage_path('framework/vite.hot'));
        }
    }

    /**
     * Load the navigation badges once per request. Every query is a round trip to the remote
     * database, so the counts are cached briefly and forgotten as soon as the underlying
     * requests change.
     */
    protected function shareNavigationData(): void
    {
        foreach (['saved', 'deleted', 'restored', 'forceDeleted'] as $event) {
            RecordRequest::$event(fn () => RecordRequest::forgetBadgeCounts());
        }

        foreach (['saved', 'deleted'] as $event) {
            DocumentRelease::$event(fn () => RecordRequest::forgetBadgeCounts());
        }

        View::composer('layouts.navigation', function ($view): void {
            $user = Auth::user();

            $statusCounts = Gate::forUser($user)->allows('viewAny', RecordRequest::class)
                ? RecordRequest::badgeCounts()
                : [];

            $view->with([
                'pendingCancellationsCount' => (int) ($statusCounts[RequestStatus::CancellationRequested->value] ?? 0),
                'pendingRequestsCount' => (int) ($statusCounts[RequestStatus::Pending->value] ?? 0),
            ]);
        });
    }

    /**
     * Limit how often the public record request form can be submitted, both per
     * connection and per student number, so it can't be used to flood the registrar.
     */
    protected function configureRateLimiting(): void
    {
        // Automated load tests all come from one address, so they raise the limits (see .env.e2e).
        $scale = max(1, (int) config('school.rate_limit_scale'));

        RateLimiter::for('record-requests', function (Request $request) use ($scale) {
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

            $limits = [Limit::perMinutes(10, 5 * $scale)->by('ip:'.$request->ip())->response($onTooManyAttempts)];

            if ($request->filled('student_no')) {
                $limits[] = Limit::perMinutes(10, 5 * $scale)
                    ->by('student:'.mb_strtolower($request->string('student_no')->toString()))
                    ->response($onTooManyAttempts);
            }

            return $limits;
        });

        RateLimiter::for('record-request-cancellations', function (Request $request) use ($scale) {
            return Limit::perMinutes(10, 5 * $scale)->by('ip:'.$request->ip())->response(function (Request $request, array $headers) {
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
        RateLimiter::for('record-request-history', function (Request $request) use ($onHistoryThrottled, $scale) {
            return [
                Limit::perMinutes(10, 5 * $scale)->by('ip:'.$request->ip())->response($onHistoryThrottled),
                Limit::perMinutes(10, 3 * $scale)->by('email:'.mb_strtolower($request->string('email')->toString()))->response($onHistoryThrottled),
            ];
        });

        RateLimiter::for('record-request-history-verify', function (Request $request) use ($onHistoryThrottled, $scale) {
            return Limit::perMinutes(10, 10 * $scale)->by('ip:'.$request->ip())->response($onHistoryThrottled);
        });

        // Reference numbers are high-entropy and hard to guess, but this still limits how
        // fast someone could script through attempts.
        RateLimiter::for('record-request-status', function (Request $request) use ($scale) {
            return Limit::perMinutes(10, 5 * $scale)->by('ip:'.$request->ip())->response(function (Request $request, array $headers) {
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
