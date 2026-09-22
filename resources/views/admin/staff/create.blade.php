<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add staff account') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <form method="POST" action="{{ route('admin.staff.store') }}" class="max-w-xl space-y-6">
                    @csrf

                    <p class="text-sm text-gray-600">
                        {{ __('The new user will be emailed a link to set their own password.') }}
                    </p>

                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus autocomplete="off" />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required autocomplete="off" />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="role" :value="__('Role')" />
                        <x-select-input id="role" name="role" class="mt-1 block w-full" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected(old('role', \App\Enums\Role::Staff->value) === $role->value)>{{ $role->label() }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error class="mt-2" :messages="$errors->get('role')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Create account') }}</x-primary-button>
                        <a href="{{ route('admin.staff.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
