<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">
            {{ __('Edit account') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 text-sm rounded-xl ring-1 ring-inset ring-green-200" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl">
                <form method="POST" action="{{ route('admin.staff.update', $account) }}" class="max-w-xl space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $account->name)" required autofocus autocomplete="off" />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $account->email)" required autocomplete="off" />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="role" :value="__('Role')" />
                        <x-select-input id="role" name="role" class="mt-1 block w-full" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected(old('role', $account->role->value) === $role->value)>{{ $role->label() }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error class="mt-2" :messages="$errors->get('role')" />
                    </div>

                    <div>
                        <input type="hidden" name="is_active" value="0">
                        <label for="is_active" class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_active', $account->is_active))>
                            {{ __('Account is active') }}
                        </label>
                        <p class="mt-1 text-xs text-gray-500">{{ __('A deactivated user is signed out and cannot log in. Their history is kept.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                        <a wire:navigate.hover href="{{ route('admin.staff.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl">
                <div class="max-w-xl">
                    @include('admin.partials.reset-password-form')
                </div>
            </div>

            @can('delete', $account)
                <div class="p-4 sm:p-8 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl">
                    <div class="max-w-xl space-y-6">
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">{{ __('Delete account') }}</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('This account will be signed out and removed from the staff list. This does not delete their history and can be recovered if needed.') }}
                            </p>
                        </header>

                        <x-danger-button
                            type="button"
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', 'confirm-staff-deletion')"
                        >{{ __('Delete account') }}</x-danger-button>

                        <x-modal name="confirm-staff-deletion" focusable>
                            <form method="POST" action="{{ route('admin.staff.destroy', $account) }}" class="p-6">
                                @csrf
                                @method('delete')

                                <h2 class="text-lg font-medium text-gray-900">
                                    {{ __('Delete this account?') }}
                                </h2>

                                <p class="mt-1 text-sm text-gray-600">
                                    {{ __(':name (:email) will be signed out and removed from the list. This does not delete their history and can be recovered if needed.', ['name' => $account->name, 'email' => $account->email]) }}
                                </p>

                                <div class="mt-6 flex justify-end">
                                    <x-secondary-button x-on:click="$dispatch('close')">
                                        {{ __('Cancel') }}
                                    </x-secondary-button>

                                    <x-danger-button class="ms-3">
                                        {{ __('Delete account') }}
                                    </x-danger-button>
                                </div>
                            </form>
                        </x-modal>
                    </div>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
