<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cancellation requests') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 text-sm sm:rounded-lg" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('requests.cancellations') }}"
                    class="rounded-md px-3 py-1.5 text-sm font-medium {{ ! $showingCancelled ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }} shadow-sm"
                >
                    {{ __('Pending') }}
                </a>
                <a
                    href="{{ route('requests.cancellations', ['status' => 'cancelled']) }}"
                    class="rounded-md px-3 py-1.5 text-sm font-medium {{ $showingCancelled ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }} shadow-sm"
                >
                    {{ __('Cancelled') }}
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Reference') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Student') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Document') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Reason') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ $showingCancelled ? __('Cancelled') : __('Requested') }}</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recordRequests as $recordRequest)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $recordRequest->reference_no }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $recordRequest->fullName() }}
                                    <span class="block text-xs text-gray-500">{{ $recordRequest->student_no }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $recordRequest->document_type->label() }}</td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs truncate" title="{{ $recordRequest->cancellation_reason }}">{{ $recordRequest->cancellation_reason }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ ($showingCancelled ? $recordRequest->cancelled_at : $recordRequest->cancellation_requested_at)->format('M j, Y g:i A') }}
                                </td>
                                <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('requests.show', $recordRequest) }}" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">{{ __('View') }}</a>

                                    @can('confirmCancellation', $recordRequest)
                                        <form method="POST" action="{{ route('requests.confirm-cancellation', $recordRequest) }}" class="inline" onsubmit="return confirm('{{ __('Confirm this cancellation? This cannot be undone.') }}');">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">{{ __('Confirm') }}</button>
                                        </form>
                                    @endcan

                                    @can('denyCancellation', $recordRequest)
                                        <form method="POST" action="{{ route('requests.deny-cancellation', $recordRequest) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">{{ __('Keep request') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    {{ $showingCancelled ? __('No requests have been cancelled.') : __('No cancellation requests are awaiting review.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $recordRequests->links() }}
        </div>
    </div>
</x-app-layout>
