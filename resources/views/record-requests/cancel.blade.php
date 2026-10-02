<x-public-layout>
    <div class="bg-gradient-to-b from-indigo-50 to-gray-50">
        <div class="mx-auto max-w-xl px-6 py-10 sm:py-14">
            <a href="/" class="group inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-indigo-600 hover:text-white hover:ring-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to home') }}
            </a>

            @if (session('status'))
                {{-- Cancellation sent --}}
                <div class="mx-auto mt-6 rounded-2xl bg-white p-8 text-center shadow-xl shadow-indigo-900/5 ring-1 ring-gray-900/5 sm:p-10" role="status">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-green-600">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>

                    <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900">{{ __('Cancellation Requested') }}</h1>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ session('status') }}</p>

                    <ol class="mt-8 space-y-4 text-left">
                        @foreach ([
                            ['The registrar reviews your request', 'Registrar staff confirm the cancellation before it takes effect.'],
                            ['Check on it any time', 'If the registrar cannot cancel it, your request stays active. Use your reference number to see where it stands.'],
                        ] as $index => [$title, $description])
                            <li class="flex gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">{{ $index + 1 }}</span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ __($title) }}</p>
                                    <p class="text-sm text-gray-600">{{ __($description) }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <a href="{{ route('record-requests.status.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">{{ __('Check Status') }}</a>
                        <a href="/" class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">{{ __('Back to home') }}</a>
                    </div>

                    <p class="mt-5 text-sm text-gray-500">
                        <a href="{{ route('record-requests.cancel.create') }}" class="font-medium text-gray-600 underline underline-offset-2 hover:text-gray-900">{{ __('Cancel another request') }}</a>
                    </p>
                </div>
            @else
                <div class="mb-8 mt-4 text-center">
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ __('Cancel a request') }}</h1>
                    <p class="mt-2 text-gray-600">{{ __('Enter your reference number, the email you used, and why you want to cancel. Registrar staff will confirm it.') }}</p>
                </div>

                @php($referenceFromLink = filled(old('reference_no', request('reference_no'))))

                <div
                    x-data="{
                        working: false,
                        confirming: false,
                        reason: @js(old('reason', '')),
                        review: {},
                        openReview(form) {
                            if (! form.reportValidity()) {
                                return;
                            }

                            this.review = {
                                reference: form.elements['reference_no'].value.trim().toUpperCase(),
                                reason: form.elements['reason'].value.trim(),
                            };
                            this.confirming = true;
                            this.$nextTick(() => this.$refs.keepButton.focus());
                        },
                        confirmCancellation() {
                            this.confirming = false;
                            this.working = true;
                            this.$refs.form.submit();
                        },
                    }"
                    x-on:pageshow.window="working = false; confirming = false"
                >
                    <form
                        method="POST"
                        action="{{ route('record-requests.cancel.store') }}"
                        class="flex flex-col gap-5 rounded-2xl bg-white p-6 shadow-xl shadow-indigo-900/5 ring-1 ring-gray-900/5 sm:p-8"
                        x-ref="form"
                        x-on:submit.prevent="openReview($event.target)"
                    >
                        @csrf

                        @error('throttle')
                            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200" role="alert">{{ $message }}</div>
                        @enderror

                        <div class="flex gap-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                            <p>{{ __('You can cancel a pending request :window of submitting. Once your request is approved, it can no longer be cancelled.', ['window' => \App\Models\RecordRequest::cancellationWindowPhrase()]) }}</p>
                        </div>

                        <div>
                            <x-input-label for="reference_no" :value="__('Reference number')" />
                            <x-text-input
                                id="reference_no"
                                class="mt-1 block w-full rounded-lg py-2.5 font-mono uppercase tracking-wider placeholder:normal-case placeholder:tracking-normal"
                                type="text"
                                name="reference_no"
                                :value="old('reference_no', request('reference_no'))"
                                required
                                :autofocus="! $referenceFromLink"
                                autocomplete="off"
                                autocapitalize="characters"
                                spellcheck="false"
                                placeholder="REQ-AB12CD34"
                            />
                            <x-input-error :messages="$errors->get('reference_no')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" class="mt-1 block w-full rounded-lg py-2.5" type="email" name="email" :value="old('email')" required :autofocus="$referenceFromLink" autocomplete="email" placeholder="juan.delacruz@gmail.com" />
                            <p class="mt-1 text-xs text-gray-500">{{ __('Use the same email you entered on the request form.') }}</p>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="reason" :value="__('Reason for cancellation')" />
                            <textarea id="reason" name="reason" rows="3" maxlength="1000" required x-model="reason" placeholder="{{ __('For example: I submitted it by mistake, or I no longer need it.') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('reason') }}</textarea>
                            <p class="mt-1 text-right text-xs text-gray-400"><span x-text="reason.length">0</span>/1000</p>
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>

                        <button
                            type="submit"
                            x-bind:disabled="working"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-red-600 px-4 py-3.5 text-base font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-60"
                        >
                            <span x-show="! working">{{ __('Request cancellation') }}</span>
                            <span x-show="working" x-cloak>{{ __('Sending…') }}</span>
                        </button>

                        <p class="text-center text-sm text-gray-600">
                            {{ __('Not sure?') }}
                            <a href="{{ route('record-requests.status.create') }}" class="font-medium text-indigo-600 underline underline-offset-2 hover:text-indigo-800">{{ __('Check your request status first') }}</a>
                        </p>
                    </form>

                    {{-- Confirm pop-up --}}
                    <div
                        x-show="confirming"
                        x-cloak
                        x-effect="document.body.classList.toggle('overflow-y-hidden', confirming)"
                        x-on:keydown.escape.window="confirming = false"
                        class="fixed inset-0 z-50 flex items-center justify-center p-4"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="confirm-cancel-title"
                    >
                        <div x-show="confirming" x-transition.opacity x-on:click="confirming = false" class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" aria-hidden="true"></div>

                        <div
                            x-show="confirming"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="scale-95 opacity-0"
                            x-transition:enter-end="scale-100 opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="scale-100 opacity-100"
                            x-transition:leave-end="scale-95 opacity-0"
                            class="relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                        >
                            <div class="flex items-start gap-4 border-b border-gray-100 p-6">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                </span>
                                <div>
                                    <h2 id="confirm-cancel-title" class="text-lg font-semibold text-gray-900">{{ __('Cancel this request?') }}</h2>
                                    <p class="mt-1 text-sm text-gray-600">{{ __('Registrar staff will review it before it is cancelled.') }}</p>
                                </div>
                            </div>

                            <dl class="space-y-3 overflow-y-auto p-6 text-sm">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Reference number') }}</dt>
                                    <dd class="font-mono font-semibold tracking-wider text-gray-900" x-text="review.reference"></dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('Reason') }}</dt>
                                    <dd class="mt-1 whitespace-pre-line break-words rounded-lg bg-gray-50 p-3 text-gray-900" x-text="review.reason"></dd>
                                </div>
                            </dl>

                            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-gray-50 p-4 sm:flex-row sm:justify-end">
                                <button type="button" x-ref="keepButton" x-on:click="confirming = false" class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    {{ __('Keep my request') }}
                                </button>
                                <button type="button" x-on:click="confirmCancellation()" class="inline-flex items-center justify-center rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                    {{ __('Yes, request cancellation') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-public-layout>
