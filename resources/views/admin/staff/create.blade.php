<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">
            {{ __('Add staff account') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl">
                <form method="POST" action="{{ route('admin.staff.store') }}" class="max-w-xl space-y-6">
                    @csrf

                    <p class="text-sm text-gray-600">
                        {{ __('We will email a confirmation link to this address first. The account is only created once the owner opens that link, so a fake or mistyped address never gets an account. After they confirm, the password you set below is emailed to them with their sign-in details.') }}
                    </p>

                    <div>
                        <x-input-label required for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus autocomplete="off" data-1p-ignore data-lpignore="true" data-bwignore="true" data-form-type="other" />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label required for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="text" class="mt-1 block w-full" :value="old('email')" required autocomplete="off" spellcheck="false" data-1p-ignore data-lpignore="true" data-bwignore="true" data-form-type="other" />
                        <p class="mt-1 text-xs text-gray-500">{{ __('Must be a real Gmail address (e.g. juan.delacruz@gmail.com).') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label required for="role" :value="__('Role')" />
                        <x-select-input id="role" name="role" class="mt-1 block w-full" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected(old('role', \App\Enums\Role::Staff->value) === $role->value)>{{ $role->label() }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error class="mt-2" :messages="$errors->get('role')" />
                    </div>

                    <div x-data="{ show: false }">
                        <x-input-label required for="password" :value="__('Password')" />
                        <div class="relative mt-1">
                            <x-text-input id="password" name="password" x-bind:type="show ? 'text' : 'password'" type="password" class="block w-full pr-11" required autocomplete="new-password" />
                            <button type="button" x-on:click="show = ! show" class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-gray-400 transition hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500" x-bind:aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))" x-bind:aria-pressed="show">
                                <svg x-show="! show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                    </div>

                    <div x-data="{ show: false }">
                        <x-input-label required for="password_confirmation" :value="__('Confirm password')" />
                        <div class="relative mt-1">
                            <x-text-input id="password_confirmation" name="password_confirmation" x-bind:type="show ? 'text' : 'password'" type="password" class="block w-full pr-11" required autocomplete="new-password" />
                            <button type="button" x-on:click="show = ! show" class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-gray-400 transition hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500" x-bind:aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))" x-bind:aria-pressed="show">
                                <svg x-show="! show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Send confirmation link') }}</x-primary-button>
                        <a wire:navigate.hover href="{{ route('admin.staff.index') }}" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
