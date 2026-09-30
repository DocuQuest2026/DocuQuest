<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
