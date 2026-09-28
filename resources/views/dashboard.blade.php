<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-1">
                    <p class="font-medium">{{ __('Welcome, :name', ['name' => Auth::user()->name]) }}</p>
                    <p class="text-sm text-gray-600">{{ Auth::user()->role->label() }}</p>
                </div>
            </div>

            @isset($actionableRequests)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">{{ __('Requests needing action') }}</h3>
                        <a href="{{ route('requests.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 underline">{{ __('View all requests') }}</a>
                    </div>

                    @if ($actionableRequests->isEmpty())
                        <p class="p-6 text-sm text-gray-500">{{ __('Nothing needs your attention right now.') }}</p>
                    @else
                        @php
                            $actionLabels = [
                                \App\Enums\RequestStatus::Pending->value => __('Needs approval'),
                                \App\Enums\RequestStatus::Approved->value => __('Ready to release'),
                                \App\Enums\RequestStatus::CancellationRequested->value => __('Cancellation requested'),
                            ];
                        @endphp
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-left text-gray-600">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Reference') }}</th>
                                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Student') }}</th>
                                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Document') }}</th>
                                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Submitted') }}</th>
                                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Action needed') }}</th>
                                        <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($actionableRequests as $recordRequest)
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ $recordRequest->reference_no }}</td>
                                            <td class="px-4 py-3 text-gray-600">
                                                {{ $recordRequest->fullName() }}
                                                <span class="block text-xs text-gray-500">{{ $recordRequest->student_no }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">{{ $recordRequest->document_type->label() }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $recordRequest->created_at->format('M j, Y') }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                                    {{ $actionLabels[$recordRequest->status->value] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ route('requests.show', $recordRequest) }}" class="text-indigo-600 hover:text-indigo-800 underline">{{ __('View') }}</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($actionableCount > $actionableRequests->count())
                            <p class="px-4 py-3 text-xs text-gray-500 border-t border-gray-100">
                                {{ __('Showing the oldest :shown of :total requests needing action.', ['shown' => $actionableRequests->count(), 'total' => $actionableCount]) }}
                            </p>
                        @endif
                    @endif
                </div>
            @endisset
        </div>
    </div>
</x-app-layout>
