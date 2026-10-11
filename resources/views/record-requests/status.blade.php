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
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ __('Check request status') }}</h1>
                <p class="mt-2 text-gray-600">{{ __('Enter the reference number you were emailed to see where your request stands.') }}</p>
            </div>

            <div class="space-y-6">
                @if ($recordRequest)
                    <livewire:request-status :reference-no="$recordRequest->reference_no" />
                @endif

                <form method="POST" action="{{ route('record-requests.status.store') }}" class="flex flex-col gap-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8" x-data="{ checking: false }" x-on:submit="checking = true" x-on:pageshow.window="checking = false">
                    @csrf

                    @error('throttle')
                        <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200" role="alert">{{ $message }}</div>
                    @enderror

                    <div>
                        <x-input-label required for="reference_no" :value="$recordRequest ? __('Check another reference number') : __('Reference number')" />

                        <div class="relative mt-1">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                            <x-text-input
                                id="reference_no"
                                class="block w-full rounded-lg py-2.5 pl-10 font-mono uppercase tracking-wider placeholder:normal-case placeholder:tracking-normal"
                                type="text"
                                name="reference_no"
                                :value="old('reference_no')"
                                required
                                :autofocus="! $recordRequest"
                                autocomplete="off"
                                autocapitalize="characters"
                                spellcheck="false"
                                placeholder="REQ-AB12CD34"
                            />
                        </div>

                        <x-input-error :messages="$errors->get('reference_no')" class="mt-2" />
                    </div>

                    <x-primary-button class="w-full justify-center rounded-xl py-3.5 text-base" x-bind:disabled="checking">
                        <span x-show="! checking">{{ __('Check status') }}</span>
                        <span x-show="checking" x-cloak>{{ __('Checking…') }}</span>
                    </x-primary-button>

                    <p class="text-center text-sm text-gray-600">
                        {{ __('Lost your reference number?') }}
                        <a href="{{ route('record-requests.history.create') }}" class="font-medium text-indigo-600 underline underline-offset-2 hover:text-indigo-800">{{ __('View all your requests by email') }}</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</x-public-layout>
