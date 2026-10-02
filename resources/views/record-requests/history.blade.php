<x-public-layout>
    <div class="bg-gradient-to-b from-indigo-50 to-gray-50">
        <div class="mx-auto max-w-xl px-6 py-10 sm:py-14">
            <a href="/" class="group inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-indigo-600 hover:text-white hover:ring-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to home') }}
            </a>

            <div class="mb-8 mt-4 text-center">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ __('Request history') }}</h1>
                <p class="mt-2 text-gray-600">
                    @if ($verifiedEmail)
                        {{ __('All requests made with :email.', ['email' => $verifiedEmail]) }}
                    @elseif ($pendingEmail)
                        {{ __('Enter the 6-digit code we emailed to :email.', ['email' => $pendingEmail]) }}
                    @else
                        {{ __('Enter the email you used when requesting a document. We will email you a code to view your past requests.') }}
                    @endif
                </p>
            </div>

            @unless ($verifiedEmail)
                {{-- Step 1 of 2 and step 2 of 2 --}}
                <ol class="mb-6 flex items-center justify-center gap-3 text-sm font-semibold" aria-label="{{ __('Steps') }}">
                    <li class="flex items-center gap-2 {{ $pendingEmail ? 'text-gray-500' : 'text-indigo-700' }}">
                        @if ($pendingEmail)
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-white">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                        @else
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs text-white">1</span>
                        @endif
                        {{ __('Email') }}
                    </li>
                    <li class="h-px w-10 {{ $pendingEmail ? 'bg-indigo-600' : 'bg-gray-300' }}" aria-hidden="true"></li>
                    <li class="flex items-center gap-2 {{ $pendingEmail ? 'text-indigo-700' : 'text-gray-400' }}">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs {{ $pendingEmail ? 'bg-indigo-600 text-white' : 'border-2 border-gray-300 text-gray-400' }}">2</span>
                        {{ __('Code') }}
                    </li>
                </ol>
            @endunless

            @if ($verifiedEmail)
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 px-1">
                        <p class="text-sm font-semibold text-gray-900">{{ trans_choice(':count request|:count requests', $recordRequests->count()) }}</p>
                        <p class="text-xs text-gray-500">{{ __('For your privacy, this view closes by itself after :minutes minutes.', ['minutes' => $accessMinutes]) }}</p>
                    </div>

                    @forelse ($recordRequests as $recordRequest)
                        @php
                            $badge = $recordRequest->requesterStatus();
                            $claimedAt = $recordRequest->status === \App\Enums\RequestStatus::Released ? $recordRequest->release?->claimed_at : null;
                            $nothingToPay = in_array($recordRequest->status, [\App\Enums\RequestStatus::Rejected, \App\Enums\RequestStatus::Cancelled], true);
                        @endphp

                        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Reference number') }}</p>
                                    <p class="mt-1 font-mono text-lg font-bold tracking-wider text-gray-900">{{ $recordRequest->reference_no }}</p>
                                </div>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $badge['classes'] }}">{{ $badge['label'] }}</span>
                            </div>

                            <dl class="mt-4 space-y-3 border-t border-gray-100 pt-4 text-sm">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Document') }}</dt>
                                    <dd class="text-right font-medium text-gray-900">
                                        {{ $recordRequest->document_type->label() }}
                                        <span class="whitespace-nowrap font-normal text-gray-500">&times; {{ trans_choice(':count copy|:count copies', $recordRequest->copies) }}</span>
                                    </dd>
                                </div>
                                @unless ($nothingToPay)
                                    <div class="flex items-start justify-between gap-4">
                                        <dt class="text-gray-500">{{ $claimedAt !== null ? __('Total fee') : __('Amount to pay') }}</dt>
                                        <dd class="text-right">
                                            <span class="block text-base font-bold text-indigo-600">{{ $recordRequest->formattedTotalFee() }}</span>
                                            <span class="block text-xs text-gray-500">{{ $recordRequest->document_type->formattedFee() }} &times; {{ trans_choice(':count copy|:count copies', $recordRequest->copies) }}</span>
                                        </dd>
                                    </div>
                                @endunless
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Submitted') }}</dt>
                                    <dd class="font-medium text-gray-900">{{ $recordRequest->created_at->format('M j, Y') }}</dd>
                                </div>
                                @if ($recordRequest->status === \App\Enums\RequestStatus::Released && $recordRequest->release?->claim_available_at)
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">{{ __('Available to claim from') }}</dt>
                                        <dd class="text-right font-medium text-gray-900">{{ $recordRequest->release->claim_available_at->format('M j, Y g:i A') }}</dd>
                                    </div>
                                @endif
                                @if ($claimedAt !== null)
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">{{ __('Claimed') }}</dt>
                                        <dd class="text-right font-medium text-gray-900">{{ $claimedAt->format('M j, Y g:i A') }}</dd>
                                    </div>
                                @endif
                            </dl>

                            @if ($recordRequest->isCancellable())
                                <p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">
                                    {{ __('You can still cancel it :window of submitting.', ['window' => \App\Models\RecordRequest::cancellationWindowPhrase()]) }}
                                    <a href="{{ route('record-requests.cancel.create', ['reference_no' => $recordRequest->reference_no]) }}" class="font-semibold underline underline-offset-2">{{ __('Cancel this request') }}</a>
                                </p>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-2xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-900/5">
                            {{ __('No requests found.') }}
                        </div>
                    @endforelse

                    <form method="POST" action="{{ route('record-requests.history.clear') }}" class="pt-2 text-center">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">{{ __('Done / use a different email') }}</button>
                    </form>
                </div>
            @elseif ($pendingEmail)
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-indigo-900/5 ring-1 ring-gray-900/5">
                    <form method="POST" action="{{ route('record-requests.history.verify') }}" class="flex flex-col gap-5 p-6 sm:p-8" x-data="{ working: false }" x-on:submit="working = true" x-on:pageshow.window="working = false">
                        @csrf

                        @error('throttle')
                            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200" role="alert">{{ $message }}</div>
                        @enderror

                        <div class="flex gap-3 rounded-xl bg-blue-50 p-4 text-sm text-blue-900 ring-1 ring-blue-200">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                            <p>{{ __('If this email has any requests, a code is on its way. It can take a minute to arrive, and it expires in :minutes minutes.', ['minutes' => $codeMinutes]) }}</p>
                        </div>

                        <div>
                            <x-input-label for="code" :value="__('6-digit code')" />
                            <x-text-input
                                id="code"
                                class="mt-1 block w-full rounded-lg py-3 text-center font-mono text-2xl tracking-[0.5em]"
                                type="text"
                                name="code"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                minlength="6"
                                pattern="[0-9]{6}"
                                required
                                autofocus
                                placeholder="123456"
                                x-on:input="$el.value = $el.value.replace(/\D/g, '').slice(0, 6)"
                            />
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>

                        <x-primary-button class="w-full justify-center rounded-xl py-3.5 text-base" x-bind:disabled="working">
                            <span x-show="! working">{{ __('View my requests') }}</span>
                            <span x-show="working" x-cloak>{{ __('Checking…') }}</span>
                        </x-primary-button>
                    </form>

                    <div class="flex flex-col items-center gap-3 border-t border-gray-100 bg-gray-50 px-6 py-5 text-sm sm:px-8">
                        <form method="POST" action="{{ route('record-requests.history.store') }}" class="flex flex-wrap items-center justify-center gap-x-3 gap-y-2">
                            @csrf
                            <input type="hidden" name="email" value="{{ $pendingEmail }}">
                            <span class="text-gray-600">{{ __('Did not get a code?') }}</span>
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3.5 py-1.5 font-semibold text-indigo-700 shadow-sm ring-1 ring-inset ring-indigo-200 transition hover:bg-indigo-600 hover:text-white hover:ring-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                {{ __('Send a new code') }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('record-requests.history.clear') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3.5 py-1.5 font-semibold text-gray-600 transition hover:bg-gray-200 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                                {{ __('Use a different email') }}
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('record-requests.history.store') }}" class="flex flex-col gap-5 rounded-2xl bg-white p-6 shadow-xl shadow-indigo-900/5 ring-1 ring-gray-900/5 sm:p-8" x-data="{ working: false }" x-on:submit="working = true" x-on:pageshow.window="working = false">
                    @csrf

                    @error('throttle')
                        <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200" role="alert">{{ $message }}</div>
                    @enderror

                    <div>
                        <x-input-label for="email" :value="__('Email')" />

                        <div class="relative mt-1">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                            <x-text-input id="email" class="block w-full rounded-lg py-2.5 pl-10" type="email" name="email" :value="old('email')" required autofocus autocomplete="email" placeholder="juan.delacruz@gmail.com" />
                        </div>

                        <p class="mt-1 text-xs text-gray-500">{{ __('Use the same email you entered on the request form.') }}</p>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <x-primary-button class="w-full justify-center rounded-xl py-3.5 text-base" x-bind:disabled="working">
                        <span x-show="! working">{{ __('Email me a code') }}</span>
                        <span x-show="working" x-cloak>{{ __('Sending…') }}</span>
                    </x-primary-button>
                </form>
            @endif
        </div>
    </div>
</x-public-layout>
