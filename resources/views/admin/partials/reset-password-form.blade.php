<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Change password') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Set a new password for :name. They are not notified and can log in with it immediately.', ['name' => $account->name]) }}</p>
    </header>

    <form method="post" action="{{ route('admin.accounts.password.update', $account) }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="reset_password_password" :value="__('New password')" />
            <x-text-input id="reset_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->resetPassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="reset_password_password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="reset_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->resetPassword->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button>{{ __('Change password') }}</x-primary-button>
    </form>
</section>
