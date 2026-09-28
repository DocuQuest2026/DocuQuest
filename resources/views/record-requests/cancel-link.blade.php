<x-public-layout>
    <div class="mx-auto max-w-md px-6 py-12 sm:py-16">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ __('Cancel a request') }}</h1>
            <p class="mt-2 text-sm text-gray-600">{{ __('Enter your reference number and the email you used. We will email you a link to cancel it.') }}</p>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('record-requests.cancel.send') }}" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
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

            <x-primary-button class="w-full justify-center">
                {{ __('Email me the cancellation link') }}
            </x-primary-button>
        </form>
    </div>
</x-public-layout>
