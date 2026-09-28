<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Student requests') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Reference') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Student') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Document') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Copies') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Submitted') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php
                            $statusColors = [
                                \App\Enums\RequestStatus::Pending->value => 'bg-yellow-100 text-yellow-800',
                                \App\Enums\RequestStatus::Approved->value => 'bg-blue-100 text-blue-800',
                                \App\Enums\RequestStatus::Released->value => 'bg-green-100 text-green-800',
                                \App\Enums\RequestStatus::Rejected->value => 'bg-red-100 text-red-800',
                                \App\Enums\RequestStatus::CancellationRequested->value => 'bg-orange-100 text-orange-800',
                                \App\Enums\RequestStatus::Cancelled->value => 'bg-gray-200 text-gray-700',
                            ];
                        @endphp
                        @forelse ($recordRequests as $recordRequest)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $recordRequest->reference_no }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $recordRequest->fullName() }}
                                    <span class="block text-xs text-gray-500">{{ $recordRequest->student_no }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $recordRequest->document_type->label() }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $recordRequest->copies }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $recordRequest->created_at->format('M j, Y g:i A') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$recordRequest->status->value] }}">{{ $recordRequest->status->label() }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('requests.show', $recordRequest) }}" class="text-indigo-600 hover:text-indigo-800 underline">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">{{ __('No requests have been submitted yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $recordRequests->links() }}
        </div>
    </div>
</x-app-layout>
