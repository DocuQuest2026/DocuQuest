<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Staff accounts') }}
            </h2>

            <a href="{{ route('admin.staff.create') }}">
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
                        @foreach ($accounts as $account)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $account->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $account->email }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $account->role->label() }}</td>
                                <td class="px-4 py-3">
                                    @if ($account->is_active)
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">{{ __('Active') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-700">{{ __('Deactivated') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.staff.edit', $account) }}" class="text-indigo-600 hover:text-indigo-800 underline">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $accounts->links() }}
        </div>
    </div>
</x-app-layout>
