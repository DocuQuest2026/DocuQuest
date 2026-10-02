<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Staff accounts') }}
            </h2>

            <a wire:navigate.hover href="{{ route('admin.staff.create') }}">
                <x-primary-button type="button">{{ __('Add account') }}</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 text-sm sm:rounded-lg" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-red-50 text-red-800 text-sm sm:rounded-lg" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="flex flex-wrap gap-2">
                <a wire:navigate.hover
                    href="{{ route('admin.staff.index') }}"
                    class="rounded-md px-3 py-1.5 text-sm font-medium {{ ! $showingDeleted ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }} shadow-sm"
                >
                    {{ __('Active accounts') }}
                </a>
                <a wire:navigate.hover
                    href="{{ route('admin.staff.index', ['deleted' => 1]) }}"
                    class="rounded-md px-3 py-1.5 text-sm font-medium {{ $showingDeleted ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }} shadow-sm"
                >
                    {{ __('Deleted accounts') }}
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Email') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Role') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($accounts as $account)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $account->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $account->email }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $account->role->label() }}</td>
                                <td class="px-4 py-3">
                                    @if ($account->trashed())
                                        <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">{{ __('Deleted') }}</span>
                                    @elseif (! $account->hasVerifiedEmail())
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800" title="{{ __('This account can not sign in until the email address is verified. If it never arrives, the email is likely fake or mistyped.') }}">{{ __('Unverified') }}</span>
                                    @elseif (! $account->is_active)
                                        <span class="inline-flex rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-700">{{ __('Deactivated') }}</span>
                                    @elseif ($account->isOnline())
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                            {{ __('Online') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                            {{ __('Offline') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                                    @if ($account->trashed())
                                        @can('restore', $account)
                                            <form method="POST" action="{{ route('admin.staff.restore', $account) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">{{ __('Restore') }}</button>
                                            </form>
                                        @endcan

                                        @can('forceDelete', $account)
                                            <x-danger-button
                                                type="button"
                                                x-data=""
                                                x-on:click.prevent="$dispatch('open-modal', 'confirm-staff-permanent-deletion-{{ $account->id }}')"
                                            >{{ __('Delete permanently') }}</x-danger-button>

                                            <x-modal name="confirm-staff-permanent-deletion-{{ $account->id }}" focusable>
                                                <form method="POST" action="{{ route('admin.staff.force-delete', $account) }}" class="p-6">
                                                    @csrf
                                                    @method('delete')

                                                    <h2 class="text-lg font-medium text-gray-900">
                                                        {{ __('Permanently delete this account?') }}
                                                    </h2>

                                                    <p class="mt-1 text-sm text-gray-600">
                                                        {{ __(':name (:email) will be erased for good. This can not be undone, and is only possible when no audit or release history references the account.', ['name' => $account->name, 'email' => $account->email]) }}
                                                    </p>

                                                    <div class="mt-6 flex justify-end">
                                                        <x-secondary-button x-on:click="$dispatch('close')">
                                                            {{ __('Cancel') }}
                                                        </x-secondary-button>

                                                        <x-danger-button class="ms-3">
                                                            {{ __('Delete permanently') }}
                                                        </x-danger-button>
                                                    </div>
                                                </form>
                                            </x-modal>
                                        @endcan
                                    @else
                                        <a wire:navigate.hover href="{{ route('admin.staff.edit', $account) }}" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">{{ __('Edit') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    {{ $showingDeleted ? __('No deleted accounts.') : __('No office accounts yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $accounts->links() }}
        </div>
    </div>
</x-app-layout>
