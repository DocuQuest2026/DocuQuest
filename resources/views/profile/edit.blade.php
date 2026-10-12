<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($user->isOfficeUser())
            <div class="p-4 sm:p-8 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-photo-form')
                </div>
            </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            @unless ($user->isStaff() || $user->isSuperAdmin())
            <div class="p-4 sm:p-8 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
            @endunless
        </div>
    </div>
</x-app-layout>
