<x-public-layout>
    <div class="mx-auto max-w-md px-6 py-12 sm:py-16">
        <a href="/" class="inline-flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            {{ __('Back to home') }}
        </a>

        <div class="mb-8 mt-4 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ __('Cancel a request') }}</h1>
            <p class="mt-2 text-sm text-gray-600">{{ __('Enter your reference number, the email you used, and why you want to cancel. Registrar staff will confirm it.') }}</p>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('status') }}</div>
        @endif

        <form
            method="POST"
            action="{{ route('record-requests.cancel.store') }}"
            class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8"
            onsubmit="return confirm('{{ __('Are you sure you want to cancel this request?') }}');"
        >
            @csrf

            @error('throttle')
                <div class="rounded-lg bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $message }}</div>
            @enderror

            <div>
                <x-input-label for="reference_no" :value="__('Reference number')" />
                <x-text-input id="reference_no" class="mt-1 block w-full" type="text" name="reference_no" :value="old('reference_no', request('reference_no'))" required autofocus placeholder="REQ-AB12CD34" />
                <x-input-error :messages="$errors->get('reference_no')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="email" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="reason" :value="__('Reason for cancellation')" />
                <textarea id="reason" name="reason" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('reason') }}</textarea>
                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
            </div>

            <x-danger-button class="w-full justify-center">
                {{ __('Request cancellation') }}
            </x-danger-button>
        </form>
    </div>
</x-public-layout>
