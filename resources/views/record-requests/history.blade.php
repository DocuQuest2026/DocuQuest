<x-public-layout>
    <div class="mx-auto max-w-md px-6 py-12 sm:py-16">
        <a href="/" class="inline-flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            {{ __('Back to home') }}
        </a>

        <div class="mb-8 mt-4 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ __('Request history') }}</h1>
            <p class="mt-2 text-sm text-gray-600">
                @if ($verifiedEmail)
                    {{ __('All requests made with :email.', ['email' => $verifiedEmail]) }}
                @elseif ($pendingEmail)
                    {{ __('Enter the 6-digit code we emailed to :email.', ['email' => $pendingEmail]) }}
                @else
                    {{ __('Enter the email you used when requesting a document. We will email you a code to view your past requests.') }}
                @endif
            </p>
        </div>

        @if ($verifiedEmail)
            @php
                $statusCopy = [
                    \App\Enums\RequestStatus::Pending->value => ['label' => __('Being processed'), 'classes' => 'bg-yellow-100 text-yellow-800'],
                    \App\Enums\RequestStatus::Approved->value => ['label' => __('Approved — being prepared'), 'classes' => 'bg-blue-100 text-blue-800'],
                    \App\Enums\RequestStatus::Released->value => ['label' => __('Ready to claim'), 'classes' => 'bg-green-100 text-green-800'],
                    \App\Enums\RequestStatus::Rejected->value => ['label' => __('Rejected'), 'classes' => 'bg-red-100 text-red-800'],
                    \App\Enums\RequestStatus::CancellationRequested->value => ['label' => __('Cancellation requested'), 'classes' => 'bg-orange-100 text-orange-800'],
                    \App\Enums\RequestStatus::Cancelled->value => ['label' => __('Cancelled'), 'classes' => 'bg-gray-200 text-gray-700'],
                ];
            @endphp

            <div class="space-y-4">
                @forelse ($recordRequests as $recordRequest)
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                        <div class="flex items-center justify-between gap-4">
                            <span class="font-semibold text-gray-900">{{ $recordRequest->reference_no }}</span>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusCopy[$recordRequest->status->value]['classes'] }}">{{ $statusCopy[$recordRequest->status->value]['label'] }}</span>
                        </div>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-4 border-t border-gray-100 pt-3">
                                <dt class="text-gray-500">{{ __('Document') }}</dt>
                                <dd class="text-gray-900">{{ $recordRequest->document_type->label() }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 border-t border-gray-100 pt-3">
                                <dt class="text-gray-500">{{ __('Copies') }}</dt>
                                <dd class="text-gray-900">{{ $recordRequest->copies }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 border-t border-gray-100 pt-3">
                                <dt class="text-gray-500">{{ __('Submitted') }}</dt>
                                <dd class="text-gray-900">{{ $recordRequest->created_at->format('M j, Y') }}</dd>
                            </div>
                            @if ($recordRequest->status === \App\Enums\RequestStatus::Released && $recordRequest->release?->claim_available_at)
                                <div class="flex justify-between gap-4 border-t border-gray-100 pt-3">
                                    <dt class="text-gray-500">{{ __('Available to claim from') }}</dt>
                                    <dd class="text-gray-900">{{ $recordRequest->release->claim_available_at->format('M j, Y g:i A') }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @empty
                    <div class="rounded-2xl bg-white p-6 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-900/5">
                        {{ __('No requests found.') }}
                    </div>
                @endforelse
            </div>

            <form method="POST" action="{{ route('record-requests.history.clear') }}" class="mt-6 text-center">
                @csrf
                <button type="submit" class="text-sm font-medium text-gray-600 underline hover:text-gray-900">{{ __('Done / use a different email') }}</button>
            </form>
        @elseif ($pendingEmail)
            <form method="POST" action="{{ route('record-requests.history.verify') }}" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
                @csrf

                @error('throttle')
                    <div class="rounded-lg bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $message }}</div>
                @enderror

                <p class="rounded-lg bg-blue-50 p-4 text-sm text-blue-800">
                    {{ __('If this email has any requests, a code is on its way. It can take a minute to arrive, and it expires in 10 minutes.') }}
                </p>

                <div>
                    <x-input-label for="code" :value="__('6-digit code')" />
                    <x-text-input id="code" class="mt-1 block w-full text-center text-lg tracking-widest" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus placeholder="123456" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <x-primary-button class="w-full justify-center">
                    {{ __('View my requests') }}
                </x-primary-button>
            </form>

            <form method="POST" action="{{ route('record-requests.history.clear') }}" class="mt-6 text-center">
                @csrf
                <button type="submit" class="text-sm font-medium text-gray-600 underline hover:text-gray-900">{{ __('Use a different email') }}</button>
            </form>
        @else
            <form method="POST" action="{{ route('record-requests.history.store') }}" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
                @csrf

                @error('throttle')
                    <div class="rounded-lg bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $message }}</div>
                @enderror

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="email" placeholder="juan.delacruz@gmail.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <x-primary-button class="w-full justify-center">
                    {{ __('Email me a code') }}
                </x-primary-button>
            </form>
        @endif
    </div>
</x-public-layout>
