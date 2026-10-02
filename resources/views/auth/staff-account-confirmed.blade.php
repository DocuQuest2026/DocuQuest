<x-guest-layout>
    @php
        $isCreated = $state === 'created';
        $isAlreadyCreated = $state === 'exists';
    @endphp

    <div class="text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full {{ $isCreated ? 'bg-green-100 text-green-600' : ($isAlreadyCreated ? 'bg-indigo-100 text-indigo-600' : 'bg-red-100 text-red-600') }}">
            @if ($isCreated)
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
            @elseif ($isAlreadyCreated)
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            @else
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
            @endif
        </span>

        @if ($isCreated)
            <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900">{{ __('Your account has been created') }}</h1>
            <p class="mt-3 text-sm leading-relaxed text-gray-600">{{ __('An administrator created a registrar\'s office account for you. Your sign-in details have been sent to your email.') }}</p>
            <p class="mt-3 break-all rounded-lg bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-200">{{ $email }}</p>
            <p class="mt-3 text-sm text-gray-600">{{ __('Check your inbox, then sign in with the password in that email.') }}</p>
        @elseif ($isAlreadyCreated)
            <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900">{{ __('Your account is already created') }}</h1>
            <p class="mt-3 text-sm leading-relaxed text-gray-600">{{ __('This confirmation link was already used. Your sign-in details were sent to your email when the account was created.') }}</p>
        @else
            <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900">{{ __('This link is not valid') }}</h1>
            <p class="mt-3 text-sm leading-relaxed text-gray-600">{{ __('This confirmation link can not be used. Ask an administrator to send you a new one.') }}</p>
        @endif

        @unless ($state === 'invalid')
            <a href="{{ route('staff.login') }}" class="mt-8 inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">{{ __('Go to staff sign in') }}</a>
        @endunless
    </div>
</x-guest-layout>
