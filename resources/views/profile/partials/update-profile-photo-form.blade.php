<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Photo') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Shown next to your name in the menu. Use a JPG, PNG or WebP image up to 2 MB.') }}
        </p>
    </header>

    <div class="mt-6 flex flex-wrap items-center gap-5">
        <x-user-avatar :user="$user" class="h-20 w-20 text-2xl" />

        <div class="flex flex-col gap-3">
            <form method="post" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3" x-data="{ chosen: false }">
                @csrf

                <input
                    id="photo"
                    name="photo"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    required
                    x-on:change="chosen = $event.target.files.length > 0"
                    class="block max-w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                />

                <x-primary-button x-bind:disabled="! chosen">{{ __('Save photo') }}</x-primary-button>
            </form>

            <x-input-error :messages="$errors->get('photo')" />

            @if ($user->profile_photo_path)
                <form method="post" action="{{ route('profile.photo.destroy') }}">
                    @csrf
                    @method('delete')

                    <button type="submit" class="text-sm font-medium text-red-600 underline underline-offset-2 hover:text-red-800">{{ __('Remove photo') }}</button>
                </form>
            @endif

            @if (in_array(session('status'), ['profile-photo-updated', 'profile-photo-removed'], true))
                <p class="text-sm text-green-700" role="status">{{ session('status') === 'profile-photo-updated' ? __('Photo saved.') : __('Photo removed.') }}</p>
            @endif
        </div>
    </div>
</section>
